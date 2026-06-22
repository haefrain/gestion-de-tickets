# Redis — Estrategia de Cache

**Prueba Técnica · IATSAE** · Fase **F5 · Servicios de plataforma**

| Campo | Valor |
|---|---|
| **Estado** | Borrador v0.1 |
| **Fecha** | 2026-06-22 |
| **Estrategia** | Cache-aside (lazy loading) |
| **Componente** | Symfony Cache (adaptador Redis) tras el puerto `Cache` |

## 1. Estrategia: Cache-aside

1. El caso de uso de lectura consulta primero Redis.
2. Si hay **hit**, devuelve el valor cacheado.
3. Si hay **miss**, lee de PostgreSQL/Elasticsearch, **puebla** la cache con TTL y devuelve.
4. Las escrituras **invalidan** explícitamente las claves afectadas.

El acceso a Redis se hace tras un puerto (`Cache`); el dominio no conoce Redis.

## 2. Qué se cachea

| Dato | Clave | TTL | Invalidación |
|---|---|---|---|
| Detalle de ticket | `ticket:{id}` | 5 min | Al editar/transicionar/asignar ese ticket |
| Listado por filtros | `tickets:list:{hashFiltros}` | 1 min | Al crear o cambiar cualquier ticket del alcance |
| Perfil de usuario | `user:{id}` | 10 min | Al actualizar el usuario |

> Los listados usan TTL corto porque su invalidación fina es costosa; el detalle usa invalidación explícita por ser puntual.

## 3. Convenciones de claves

- Prefijo por entidad y namespace de entorno: `iatsae:{env}:ticket:{id}`.
- Los filtros de listado se normalizan y se hashean para formar la clave.
- Serialización JSON; valores compactos (solo lo necesario para la vista).

## 4. Invalidación

- **Detalle:** al confirmar una escritura sobre el ticket se borra `ticket:{id}`.
- **Listados:** se usan *tags* de cache (Symfony Cache Tags) para invalidar grupos (`tag: tickets`) en una operación.
- Regla: nunca servir datos obsoletos tras una escritura confirmada.

## 5. Otros usos de Redis

| Uso | Detalle |
|---|---|
| **Rate limiting** | Contador por IP/usuario en login (`rl:login:{ip}`) con ventana deslizante |
| **Revocación de refresh tokens** | Lista de tokens revocados con TTL = expiración del token |
| **Locks** (futuro) | Symfony Lock sobre Redis para secciones críticas (p. ej. reindexado) |

## 6. Pendiente de iterar

- Confirmar TTLs exactos según comportamiento observado.
- Decidir si se cachean los resultados de búsqueda (ES ya es rápido; podría no aportar).
- Definir métricas de hit ratio para validar la estrategia.
