# L5 · Rendimiento y Cache

**Backlog rico** · Transversal (Ticketing / Shared) · [↩ índice](../backlog.md)

> Cache con Redis (estrategia **cache-aside**) para acelerar lecturas frecuentes, con invalidación coherente ante escrituras. Acceso tras el puerto `Cache`. Redis también soporta rate limiting.

---

## Épica L5-E1 · Cache de lecturas

### HU-L5-E1-01 · Cachear detalle y listados

**Narrativa:** Como **sistema** quiero **cachear lecturas frecuentes** para **reducir carga y latencia**.

| Metadato | Valor |
|---|---|
| **Contexto** | Shared / Ticketing · **Épica:** L5-E1 |
| **Prioridad** | Must · **Estimación:** 3 · **Labels:** `area:cache`, `type:story` |
| **Depende de** | HU-L2-E1-02, HU-L2-E1-03 |

**Criterios de aceptación**

```gherkin
Escenario: Hit de cache
  Dado un ticket ya consultado
  Cuando se vuelve a pedir su detalle
  Entonces se sirve desde Redis sin tocar PostgreSQL

Escenario: Miss de cache
  Dado un dato no cacheado
  Cuando se solicita
  Entonces se lee de la BD, se puebla la cache con TTL y se devuelve
```

**Diseño técnico:** lectura cache-aside en los handlers de query; claves `ticket:{id}` (TTL 5 min) y `tickets:list:{hash}` (TTL 1 min, tag `tickets`); adaptador sobre **Symfony Cache** (Redis).

**Infraestructura:** **Redis** (namespace `iatsae:{env}:`).

**Tests** · *Integración:* segunda lectura no consulta BD (verificado con espía del repositorio).

---

### HU-L5-E1-02 · Invalidación en escrituras

**Narrativa:** Como **sistema** quiero **invalidar la cache al escribir** para **no servir datos obsoletos**.

| Metadato | Valor |
|---|---|
| **Contexto** | Shared / Ticketing · **Épica:** L5-E1 |
| **Prioridad** | Must · **Estimación:** 3 · **Labels:** `area:cache`, `type:story` |
| **Depende de** | HU-L5-E1-01 |

**Criterios de aceptación**

```gherkin
Escenario: Invalidación puntual
  Cuando se edita o transiciona un ticket
  Entonces se borra ticket:{id}

Escenario: Invalidación de listados
  Cuando se crea o cambia cualquier ticket
  Entonces se invalida la tag "tickets"
  Y ninguna lectura posterior devuelve datos obsoletos
```

**Diseño técnico:** los handlers de escritura invalidan claves/tags tras confirmar la persistencia (cache tags de Symfony).

**Infraestructura:** **Redis** (invalidación por tag).

**Tests** · *Integración:* tras una escritura confirmada, la lectura refleja el nuevo valor.

---

## Épica L5-E2 · Protección de acceso

### HU-L5-E2-01 · Rate limiting en login

**Narrativa:** Como **equipo** quiero **limitar intentos de login** para **mitigar fuerza bruta**.

| Metadato | Valor |
|---|---|
| **Contexto** | Identity / Shared · **Épica:** L5-E2 |
| **Prioridad** | Could · **Estimación:** 3 · **Labels:** `area:cache`, `type:story` |
| **Depende de** | HU-L1-E1-02 |

**Criterios de aceptación**

```gherkin
Escenario: Bloqueo temporal
  Dado N intentos de login fallidos por IP/usuario
  Cuando se supera el umbral
  Entonces se aplica un bloqueo temporal con 429
```

**Contrato API** · afecta a `POST /api/v1/login` → `429 Too Many Requests` al superar el umbral.

**Diseño técnico:** Symfony RateLimiter con almacén Redis (ventana deslizante); clave `rl:login:{ip}`.

**Infraestructura:** **Redis** (contador con TTL).

**Tests** · *Integración:* tras N fallos, el siguiente intento recibe `429`.
