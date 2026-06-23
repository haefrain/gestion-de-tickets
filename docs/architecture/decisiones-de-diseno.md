# Decisiones de diseño y sus motivos

**Prueba Técnica · IATSAE**

| Campo | Valor |
|---|---|
| **Propósito** | Explicar **por qué** el sistema está construido como está — el razonamiento detrás de cada decisión, no solo el resultado. |
| **Para quién** | Quien lee el código por primera vez y se pregunta "¿por qué esto está acá / hecho así?". |
| **Relación con otros docs** | [`overview.md`](overview.md) describe **qué** es la arquitectura · [`patterns.md`](patterns.md) cataloga los **patrones** aplicados · los [ADR](adr/README.md) registran decisiones formales puntuales. Este documento **conecta los porqués** y cubre además decisiones de implementación que no tienen ADR propio. |

> **Cómo leerlo:** cada decisión sigue el formato **Qué · Por qué · Alternativa descartada**. Donde existe un ADR formal, se enlaza para el detalle.

---

## 1. Decisiones macro de arquitectura

### 1.1 Arquitectura hexagonal (puertos y adaptadores)
- **Qué:** el dominio no conoce Symfony, Doctrine ni ningún cliente de infraestructura. `Application` define **puertos** (interfaces) y `Infrastructure` los implementa (**adaptadores**). Las dependencias apuntan **hacia adentro**.
- **Por qué:** la lógica de negocio queda aislada y testeable sin levantar el framework ni la base de datos; cambiar un proveedor (ej. el cliente de Elasticsearch) no toca los casos de uso. Es el requisito no negociable de la prueba.
- **Alternativa descartada:** arquitectura en capas tradicional con el ORM filtrándose al dominio → acopla las reglas de negocio a la persistencia y dificulta los tests. Ver [ADR 0001](adr/0001-arquitectura-hexagonal.md).

### 1.2 Organización por bounded context, no por tipo técnico
- **Qué:** el código se agrupa por **dominio** (`Identity/`, `Ticketing/`, `Search/`, `Notifications/`, `Shared/`), y dentro de cada uno están sus tres capas (`Domain/`, `Application/`, `Infrastructure/`). Cada pieza técnica (controllers, entidades, adaptadores) vive en la capa correspondiente de su contexto.
- **Por qué:** todo lo de una capacidad vive junto, así un cambio en "tickets" se localiza en un módulo. Hace explícitos los límites entre contextos y prepara una posible extracción a servicios.
- **Alternativa descartada:** estructura por capa técnica (`src/Controller`, `src/Service`, `src/Entity`) — la convención por defecto de Symfony. Dispersa una misma feature por muchas carpetas y diluye los límites de dominio. Ver [ADR 0002](adr/0002-bounded-contexts.md).

### 1.3 Monolito modular
- **Qué:** un solo despliegue, pero internamente modular por contexto, comunicado por **eventos de dominio** (no llamadas directas entre contextos).
- **Por qué:** la simplicidad operativa de un monolito con el desacople de microservicios, sin su costo. Cada contexto puede evolucionar de forma independiente.
- **Alternativa descartada:** microservicios desde el día uno → sobre-ingeniería para el alcance del MVP (latencia de red, despliegues múltiples, consistencia distribuida).

### 1.4 CQRS ligero
- **Qué:** escritura por **Comandos** (`*Command` + `*Handler`), lectura por **Consultas** (`*Query` + `*Handler`). La lectura puede saltarse el modelo de escritura (la búsqueda lee de Elasticsearch).
- **Por qué:** separa intenciones; cada handler hace una cosa (SRP) y es fácil de testear. Permite optimizar las lecturas (cache, índice) sin tocar la escritura. "Ligero" = sin event sourcing ni bases separadas; solo la separación de caminos.
- **Alternativa descartada:** servicios "gordos" con muchos métodos mezclando lectura y escritura → difíciles de mantener y testear. Ver [ADR 0005](adr/0005-cqrs-ligero.md).

---

## 2. Capa de entrega HTTP

### 2.1 Controllers delgados e invocables
- **Qué:** cada endpoint es un controller **invocable** (`__invoke`) en `<Contexto>/Infrastructure/Http/`, sin extender `AbstractController`. Solo: lee el request, arma un Command/Query, lo despacha por el bus y devuelve la respuesta. **Cero lógica de negocio.**
- **Por qué:** el controller es un **adaptador de entrada**: traduce HTTP ↔ dominio y nada más. Mantenerlo delgado evita la "lógica en el controller" y deja el negocio en `Application`/`Domain` (testeable sin HTTP). Uno por operación = responsabilidad única y archivos pequeños.
- **Alternativa descartada:** controllers con varios métodos heredando de `AbstractController` y con lógica embebida → acoplan negocio a HTTP/Symfony y crecen sin control.

### 2.2 Registro explícito de controllers
- **Qué:** los controllers se declaran en `config/services.yaml` con `controller.service_arguments`.
- **Por qué:** da la inyección de argumentos por request manteniendo cada controller dentro de su contexto de dominio.
- **Alternativa descartada:** el autodescubrimiento por convención de carpeta → obligaría a agrupar los controllers fuera de su contexto.

### 2.3 DTOs de entrada validados (`#[MapRequestPayload]`)
- **Qué:** el cuerpo del request se mapea a un DTO inmutable (`CreateTicketRequest`, etc.) con validación declarativa, antes de llegar al caso de uso.
- **Por qué:** valida y tipa la entrada en el borde; el handler recibe datos ya saneados. Desacopla el contrato HTTP del modelo interno.
- **Alternativa descartada:** leer `$request->get(...)` a mano dentro del controller → validación dispersa y propensa a errores.

### 2.4 Errores como RFC 7807 (`application/problem+json`)
- **Qué:** un único `ProblemJsonSubscriber` traduce las excepciones de dominio (`NotFoundException`, `ForbiddenException`, `ValidationFailedException`, …) a respuestas `problem+json` con el status correcto.
- **Por qué:** formato de error estándar y consistente para todos los endpoints, centralizado en un solo lugar; el dominio lanza excepciones expresivas sin saber de HTTP.
- **Alternativa descartada:** construir respuestas de error a mano en cada controller → formato inconsistente y duplicación.

### 2.5 Paginación por cursor
- **Qué:** los listados (`/tickets`, `/search/tickets`, `/users`) paginan por **cursor** (`next_cursor`/`has_more`), no por `offset`/`page`.
- **Por qué:** el cursor es estable ante inserciones concurrentes (no "salta" ni repite filas) y rinde mejor en conjuntos grandes. El orden estable `(created_at DESC, id DESC)` lo hace determinista.
- **Alternativa descartada:** paginación por offset → degrada con el tamaño y puede duplicar/omitir filas si los datos cambian entre páginas.

---

## 3. Aplicación (casos de uso)

### 3.1 Buses sobre Symfony Messenger (command / query / event)
- **Qué:** tres buses — `command.bus` (1 handler, transaccional + validación), `query.bus` (1 handler, devuelve resultado) y `event.bus` (N handlers, async). El dominio/aplicación los usa por **puerto** (`CommandBus`, `QueryBus`, `EventBus`); el adaptador es Messenger.
- **Por qué:** un único mecanismo para despachar intenciones con middlewares (transacción Doctrine, validación) y enrutado async sin acoplar la aplicación a Messenger (se usa detrás de una interfaz).
- **Alternativa descartada:** invocar handlers directamente con `new` → pierde middlewares (transacción, validación) y acopla el llamador al handler concreto.

### 3.2 Un commit transaccional por comando
- **Qué:** el `command.bus` envuelve cada comando en una transacción Doctrine (middleware `doctrine_transaction`).
- **Por qué:** la escritura es atómica; si el handler falla, no quedan estados a medias.
- **Alternativa descartada:** manejar transacciones a mano en cada handler → repetitivo y fácil de olvidar.

---

## 4. Dominio

### 4.1 Dominio puro (sin framework ni anotaciones de ORM)
- **Qué:** las clases de `Domain/` son PHP plano. El mapeo a Doctrine vive en `Infrastructure` como **XML**, no como atributos en las entidades.
- **Por qué:** mantiene el dominio independiente del ORM; se puede testear y razonar sin Doctrine. El mapeo XML evita "ensuciar" el agregado con detalles de persistencia.
- **Alternativa descartada:** atributos `#[ORM\...]` sobre las clases de dominio → acopla el modelo de negocio a Doctrine. Ver [ADR 0004](adr/0004-dominio-desacoplado-doctrine.md).

### 4.2 Agregados con invariantes (nada de dominio anémico)
- **Qué:** `Ticket` se crea por **named constructor** (`Ticket::create`), nace en estado `open`, valida sus invariantes (título no vacío, transiciones válidas) y registra sus eventos. El estado no se cambia con setters públicos.
- **Por qué:** un agregado nunca puede existir en estado inválido; la lógica vive con los datos que protege.
- **Alternativa descartada:** entidades con getters/setters y la lógica en servicios (dominio anémico) → reglas dispersas y estados imposibles.

### 4.3 Identificadores UUID v7
- **Qué:** todos los IDs son UUID v7 (`symfony/uid`).
- **Por qué:** son ordenables por tiempo de creación (mejor localidad en índices que UUID v4) y se pueden generar en el cliente/dominio sin ida y vuelta a la base. Ver [ADR 0003](adr/0003-identificadores-uuid-v7.md).
- **Alternativa descartada:** autoincremental de base de datos → obliga a persistir para conocer el ID y filtra detalles de la base; UUID v4 → fragmenta los índices.

### 4.4 IDs de otros contextos como `string`
- **Qué:** dentro de `Ticketing`, el `requesterId`/`assigneeId` (de Identity) se guardan como `string`, no como objetos `UserId` de Identity.
- **Por qué:** evita que un contexto dependa de las clases de otro; el límite entre contextos se respeta. La referencia cruzada es solo un identificador.
- **Alternativa descartada:** importar `Identity\UserId` en `Ticketing` → acopla los contextos y rompe el aislamiento.

---

## 5. Plataforma e infraestructura

### 5.1 Eventos de dominio asíncronos (RabbitMQ + worker)
- **Qué:** todo `DomainEvent` se enruta a RabbitMQ (`async_events`) y lo consume un worker. Reintentos con backoff (1s/2s/4s) y **DLQ** (`failed`) al agotar. El envío se difiere hasta confirmar la transacción de escritura (`DispatchAfterCurrentBus`).
- **Por qué:** los efectos secundarios (indexar, notificar) no penalizan la latencia del request ni lo hacen fallar si un consumidor cae; el reintento + DLQ dan resiliencia. Diferir el envío evita publicar un evento de una transacción que luego falla (dual-write).
- **Alternativa descartada:** indexar y notificar dentro del handler de escritura → acopla, penaliza la latencia y un fallo en el indexado tira la creación del ticket. Ver [ADR 0007](adr/0007-indexado-asincrono-y-busqueda.md).

### 5.2 Búsqueda en Elasticsearch desacoplada por eventos
- **Qué:** `Search` no llama a `Ticketing`; consume los **eventos publicados** (contrato de integración) y relee el estado por SQL para indexar. El consumidor es **idempotente** (upsert por id).
- **Por qué:** mantiene los contextos desacoplados; añadir/cambiar la búsqueda no toca el núcleo. La idempotencia tolera reintentos sin duplicar.
- **Alternativa descartada:** que `Ticketing` escriba directamente en el índice → lo acopla a Elasticsearch y mezcla responsabilidades.

### 5.3 Redis para tres cosas distintas (cache, rate limit, refresh)
- **Qué:** Redis se usa como **cache-aside** del detalle de tickets (pool con *tags*, TTL 300s e invalidación en escritura), como almacén del **rate limiter** de login, y como **store del refresh token**.
- **Por qué:** un mismo servicio cubre tres necesidades de baja latencia/expiración; los *tags* permiten invalidar por entidad sin barrer todo el cache.
- **Alternativa descartada:** cachear en memoria del proceso → no se comparte entre instancias ni sobrevive a reinicios.

### 5.4 JWT RS256 (access en memoria) + refresh en cookie HttpOnly
- **Qué:** access token JWT firmado con RS256 (vida corta), entregado en el body y guardado **en memoria** por el frontend; refresh token **opaco** en cookie `HttpOnly` + `SameSite=Strict`, rotado en cada uso.
- **Por qué:** el access en memoria no es accesible por JS (mitiga XSS de robo de token); el refresh en cookie HttpOnly no es legible por JS y, con `SameSite=Strict`, es inmune a CSRF por diseño. RS256 permite verificar con la clave pública sin compartir el secreto.
- **Alternativa descartada:** guardar el JWT en `localStorage` → expuesto a XSS; CSRF token *stateful* → exige sesión de servidor, incompatible con el firewall *stateless*. Ver [ADR 0006](adr/0006-refresh-token-en-cookie-httponly.md).

### 5.5 Rate limiting en el login
- **Qué:** límite por IP en `POST /login` (ventana deslizante), devolviendo `429` al excederse.
- **Por qué:** frena ataques de fuerza bruta sobre credenciales sin afectar el uso normal.
- **Alternativa descartada:** sin límite → expone el login a prueba sistemática de contraseñas.

---

## 6. Frontend

### 6.1 Datos de servidor con TanStack Query (no `fetch` suelto)
- **Qué:** todas las lecturas/mutaciones pasan por hooks de TanStack Query (`useTickets`, `useTicket`, …) sobre un `apiClient` único.
- **Por qué:** cache, revalidación e invalidación automáticas; un solo lugar para la URL base, el `Authorization` y la normalización de errores.
- **Alternativa descartada:** `fetch` dentro de cada componente → mezcla UI y datos, duplica manejo de estado de carga/error.

### 6.2 Sesión: refresh-on-401 + restauración al montar
- **Qué:** el `apiClient` ante un `401` intenta **un** refresh y reintenta; el `AuthProvider` al montar restaura la sesión vía `/token/refresh` → `/me`. `RequireAuth` espera ese arranque antes de decidir.
- **Por qué:** la sesión sobrevive a un *reload* y a la expiración del access token sin que el usuario tenga que volver a loguearse, aprovechando el refresh token de la cookie. (Corrige el caso en que el token en memoria se perdía al recargar.)
- **Alternativa descartada:** exigir login tras cada recarga/expiración → mala experiencia, desaprovecha el refresh disponible.

---

## 7. Calidad y observabilidad

### 7.1 Análisis estático estricto + límites verificados
- **Qué:** PHPStan **nivel 9**, CS-Fixer, Rector y **Deptrac** (que verifica que las dependencias entre capas/contextos respeten la regla hexagonal) corren en CI y son bloqueantes.
- **Por qué:** la arquitectura no se mantiene sola; Deptrac falla el build si alguien importa Doctrine en el dominio o cruza un límite de contexto. Lo que está documentado se hace cumplir automáticamente.
- **Alternativa descartada:** confiar en revisión manual de la arquitectura → se erosiona con el tiempo.

### 7.2 Gate de cobertura sobre Domain/Application
- **Qué:** CI exige **≥ 80%** de cobertura en `Domain`/`Application` (la lógica crítica), medido sobre la suite Unit+Functional; es bloqueante.
- **Por qué:** concentra la exigencia donde importa (las reglas de negocio), sin inflar el número cubriendo infraestructura trivial.
- **Alternativa descartada:** un % global sobre todo el código → premia cubrir adaptadores triviales y diluye el foco en el dominio.

### 7.3 Errores en GlitchTip (compatible con Sentry)
- **Qué:** las excepciones **no controladas (5xx)** del backend se reportan a GlitchTip vía el SDK de Sentry. Los 4xx de dominio (ya traducidos a RFC 7807) se **excluyen** para no generar ruido. Sin DSN configurado, es no-op (CI y tests intactos).
- **Por qué:** observabilidad de fallos reales en local sin contaminar con errores de negocio esperados (un 404 o un 401 no es un fallo del sistema).
- **Alternativa descartada:** enviar todas las excepciones → inunda el panel con 4xx esperados y oculta los fallos reales.

---

## 8. Mapa decisión → ADR

| Tema | Documento de detalle |
|---|---|
| Hexagonal + DDD + monolito modular | [ADR 0001](adr/0001-arquitectura-hexagonal.md) |
| Bounded contexts + eventos | [ADR 0002](adr/0002-bounded-contexts.md) |
| UUID v7 | [ADR 0003](adr/0003-identificadores-uuid-v7.md) |
| Dominio desacoplado de Doctrine | [ADR 0004](adr/0004-dominio-desacoplado-doctrine.md) |
| CQRS ligero | [ADR 0005](adr/0005-cqrs-ligero.md) |
| Refresh token en cookie HttpOnly | [ADR 0006](adr/0006-refresh-token-en-cookie-httponly.md) |
| Indexado asíncrono y búsqueda | [ADR 0007](adr/0007-indexado-asincrono-y-busqueda.md) |
| Controllers delgados · RFC 7807 · cursor · buses · cache · rate limit · GlitchTip · frontend | *Este documento* (sin ADR propio) |

> **Principio rector transversal** (de [`patterns.md`](patterns.md)): un patrón o una pieza de infraestructura se incorpora **solo cuando resuelve un problema concreto**. Aplicar algo "por si acaso" es sobre-ingeniería.
