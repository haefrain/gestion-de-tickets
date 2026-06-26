# Catálogo de Patrones de Diseño — Casos Concretos

**Prueba Técnica · IATSAE** · Fase **F3 · Patrones**

| Campo | Valor |
|---|---|
| **Estado** | Estable · v1.0 |
| **Fecha** | 2026-06-22 |
| **Alcance** | Solo patrones **aplicados** en el proyecto, con su caso real y su anti-caso |
| **Relacionados** | [`architecture/overview.md`](overview.md) · [ADRs](adr/README.md) |

## Principio rector

> Un patrón se aplica solo cuando resuelve un problema concreto del dominio. Aplicarlo "por si acaso" es sobre-ingeniería. Cada entrada incluye el **caso real**, la **justificación**, el **anti-caso** (cuándo NO usarlo) y la **alternativa descartada**.

## Resumen

| Patrón | Categoría | Dónde se aplica |
|---|---|---|
| Factory / Named Constructor | Creacional | Construcción válida de `Ticket` |
| Value Object | Creacional | `Priority`, `TicketStatus`, `Email` |
| Repository | Acceso a datos | Persistencia de agregados |
| Adapter | Estructural | Clientes de Redis, RabbitMQ, Elasticsearch |
| Decorator | Estructural | Cache de lecturas sobre el repositorio |
| Data Mapper / DTO | Estructural | Mapeo dominio ↔ persistencia ↔ API |
| Strategy | Comportamiento | Reglas de asignación / prioridad |
| Command + Handler | Comportamiento | Escritura (CQRS) |
| Query + Handler | Comportamiento | Lectura (CQRS) |
| Domain Events (Observer) | Comportamiento | Reacción async a cambios del ticket |
| Specification | Comportamiento | Filtros de búsqueda componibles |
| Dependency Injection | Arquitectónico | Inyección de puertos |
| Provider (Context) · Hooks · API Service | Frontend | Auth/JWT y acceso a datos en React |

---

## Creacionales

### Factory / Named Constructor

- **Caso concreto:** `Ticket::create(...)` construye el agregado en estado `open`, asigna el `TicketId` (UUID v7) y registra `TicketCreated`.
- **Justificación:** garantiza que un ticket nunca nace en estado inválido; centraliza las invariantes de creación.
- **Anti-caso:** objetos simples sin invariantes (un DTO de respuesta) no necesitan factory; basta el constructor.
- **Alternativa descartada:** constructor público con setters → permite estados intermedios inválidos.

### Value Object

- **Caso concreto:** `Priority`, `TicketStatus` y `Email` como objetos inmutables con validación propia; `TicketStatus` encapsula las transiciones válidas.
- **Justificación:** elimina "primitivos obsesivos", centraliza validación e iguala por valor.
- **Anti-caso:** datos que sí tienen identidad y ciclo de vida (el propio Ticket) deben ser entidades, no VOs.
- **Alternativa descartada:** `string`/`int` sueltos → validación dispersa y estados imposibles.

---

## Estructurales

### Repository

- **Caso concreto:** `TicketRepository` (puerto en `Application`) con adaptador `DoctrineTicketRepository` en `Infrastructure`.
- **Justificación:** el dominio persiste sin conocer Doctrine; permite repositorio en memoria para tests.
- **Anti-caso:** consultas de solo lectura muy específicas (búsqueda) van por el lado *query* (Elasticsearch), no por el repositorio de escritura.
- **Alternativa descartada:** Active Record (entidad que se persiste a sí misma) → acopla dominio y persistencia.

### Adapter

- **Caso concreto:** clientes de Elasticsearch, Redis y RabbitMQ envueltos tras puertos (`SearchIndex`, `Cache`, `EventPublisher`).
- **Justificación:** aísla las librerías de terceros; cambiar de cliente no toca la aplicación.
- **Anti-caso:** no envolver código propio que ya controlas y es estable; añade indirección inútil.
- **Alternativa descartada:** usar el SDK del proveedor directamente en los casos de uso → acoplamiento duro.

### Decorator

- **Caso concreto:** `CachedTicketReadModel` decora el lector de tickets añadiendo cache Redis sin modificarlo.
- **Justificación:** añade caché de forma transparente respetando Open/Closed.
- **Anti-caso:** si la lógica de cache necesita conocer el detalle interno del componente, un decorador "ciego" no encaja.
- **Alternativa descartada:** meter `if (cache) ...` dentro del lector → mezcla responsabilidades.

### Data Mapper / DTO

- **Caso concreto:** mapeo entre `Ticket` (dominio) y su entidad de persistencia Doctrine, y entre dominio y los DTO de respuesta de la API.
- **Justificación:** mantiene el dominio puro y desacopla el contrato HTTP del modelo interno.
- **Anti-caso:** para scripts triviales sin capas, mapear todo añade ceremonia sin valor.
- **Alternativa descartada:** exponer la entidad de dominio directamente en la API → filtra el modelo interno.

---

## Comportamiento

### Strategy

- **Caso concreto:** estrategia de asignación de agente (`LeastBusyAssignment`, futura `RoundRobin`) y reglas de prioridad intercambiables.
- **Justificación:** permite cambiar el algoritmo sin tocar el caso de uso; cumple Open/Closed.
- **Anti-caso:** si solo hay una regla y no se prevén variantes, una estrategia es indirección prematura.
- **Alternativa descartada:** `switch` por tipo de regla dentro del handler → crece y viola SRP.

### Command + Handler

- **Caso concreto:** `CreateTicketCommand`, `ChangeTicketStatusCommand`, `AssignTicketCommand` despachados por el CommandBus a su handler.
- **Justificación:** una intención = un objeto = un handler enfocado (SRP); base del lado escritura de CQRS.
- **Anti-caso:** una lectura simple no debe ser un comando; usa una query.
- **Alternativa descartada:** servicios "gordos" con muchos métodos → difíciles de testear y mantener.

### Query + Handler

- **Caso concreto:** `SearchTicketsQuery` y `ListTicketsQuery` resueltas por handlers que leen de Elasticsearch/Redis.
- **Justificación:** lecturas optimizables sin pasar por el modelo de escritura.
- **Anti-caso:** operaciones que mutan estado nunca van por una query.
- **Alternativa descartada:** reutilizar el repositorio de escritura para listados pesados → acopla y limita la optimización.

### Domain Events (Observer / Publish-Subscribe)

- **Caso concreto:** `TicketCreated`/`TicketStatusChanged`/`TicketAssigned` publicados; Search y Notifications reaccionan de forma asíncrona.
- **Justificación:** desacopla el núcleo de sus efectos secundarios; añadir un consumidor no toca Ticketing.
- **Anti-caso:** un efecto que debe ocurrir síncrona y transaccionalmente con la escritura no debería diferirse a un evento async.
- **Alternativa descartada:** llamar a indexado y notificación dentro del handler → acopla y penaliza la latencia.

### Specification

- **Caso concreto:** filtros de búsqueda componibles (`ByStatus`, `ByPriority`, `ByAssignee`) que se combinan para construir la consulta.
- **Justificación:** reglas de filtrado reutilizables y testeables, combinables con AND/OR.
- **Anti-caso:** con uno o dos filtros fijos, una especificación es más compleja que un parámetro directo.
- **Alternativa descartada:** concatenar condiciones a mano en cada consulta → duplicación y errores.

---

## Arquitectónico

### Dependency Injection

- **Caso concreto:** el contenedor de Symfony inyecta las implementaciones de los puertos (repositorios, buses, adaptadores) por interfaz.
- **Justificación:** invierte dependencias (D de SOLID); facilita sustituir adaptadores en tests.
- **Anti-caso:** no inyectar value objects ni datos; se construyen, no se resuelven del contenedor.
- **Alternativa descartada:** instanciar dependencias con `new` dentro de las clases → acoplamiento e imposibilidad de mockear.

---

## Frontend (React)

### Provider (Context)

- **Caso concreto:** `AuthProvider` expone el estado de sesión y el JWT a toda la app.
- **Justificación:** evita *prop drilling* del token y centraliza la sesión.
- **Anti-caso:** estado local de un único componente no necesita Context.
- **Alternativa descartada:** pasar el token por props en cada nivel → frágil y repetitivo.

### Custom Hooks

- **Caso concreto:** `useTickets`, `useTicket(id)` encapsulan la obtención de datos y el estado de carga/error.
- **Justificación:** separa la lógica de datos de la presentación y la hace reutilizable.
- **Anti-caso:** lógica trivial de una sola vista no justifica un hook propio.
- **Alternativa descartada:** llamadas `fetch` dentro de los componentes → mezcla UI y datos, difícil de testear.

### API Service (Repository en cliente)

- **Caso concreto:** un módulo `apiClient` centraliza las llamadas HTTP, adjunta el JWT y normaliza errores.
- **Justificación:** un único punto para autenticación, base URL y manejo de errores.
- **Anti-caso:** una app de una sola llamada no necesita una capa de servicio.
- **Alternativa descartada:** URLs y headers repetidos por componente → inconsistencia y duplicación.

---

## Anti-patrones a evitar

| Anti-patrón | Síntoma | Cómo lo evitamos |
|---|---|---|
| **Dominio anémico** | Entidades solo con getters/setters y lógica en servicios | Invariantes y comportamiento dentro del agregado |
| **God Service** | Una clase que lo hace todo | Command/Query handlers pequeños (SRP) |
| **Sobre-ingeniería** | Patrones sin problema que resolver | Aplicar solo los casos de este catálogo |
| **Acoplamiento al framework** | Symfony/Doctrine en el dominio | Puertos y adaptadores (hexagonal) |
