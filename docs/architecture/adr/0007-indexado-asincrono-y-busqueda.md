# ADR 0007 · Indexado asíncrono y búsqueda desacoplada (Search ← eventos de Ticketing)

- **Estado:** Aceptada · 2026-06-23

## Contexto

El contexto **Search** (L3) mantiene un índice de tickets en Elasticsearch y debe quedar **al día** cuando un ticket se crea, cambia de estado o se asigna (L2). El procesamiento ocurre **fuera del request** vía RabbitMQ (L4). Hay que decidir tres cosas:

1. **Cómo se entera Search de los cambios** sin acoplarse a las entrañas de Ticketing (los dos son bounded contexts y el ADR 0002 manda comunicarlos por eventos, no por llamadas directas).
2. **Cómo se evita el *race* del dual-write:** el caso de uso escribe en PostgreSQL dentro del `doctrine_transaction` del `command.bus` y publica el evento. Si el evento llega al broker **antes** de confirmar la transacción, el worker puede consumirlo y releer un ticket que aún no está commiteado → indexa nada o datos viejos.
3. **Con qué cliente habla el adaptador con Elasticsearch**, dado que no hay cliente PHP dedicado en el stack.

## Decisión

**1. Los eventos de dominio publicados son el contrato de integración.** `Search\IndexTicketHandler` consume `TicketCreated`/`TicketStatusChanged`/`TicketAssigned` (objetos inmutables que sólo cargan ids). Para construir el documento, Search **relee el estado actual** por un puerto propio `TicketReadModel`, cuyo adaptador consulta la tabla `tickets` por SQL (DBAL). Search **no importa clases internas** de Ticketing (ni repositorio, ni agregado, ni `TicketView`): el único acoplamiento es a los eventos, que son la API pública del contexto. Es el mismo patrón aceptado para resolver nombres (lectura cruzada por SQL, sin acoplar clases).

**2. El envío al broker se difiere hasta después del commit** con `DispatchAfterCurrentBusStamp`. El `MessengerEventBus` estampa cada evento; el `dispatch_after_current_bus` middleware (compartido entre buses) retiene el envío hasta que el bus raíz termina —es decir, hasta que `doctrine_transaction` confirma—. Así el worker nunca consume el evento de un ticket no commiteado.

**3. Indexado idempotente por id.** El upsert es `PUT /tickets/_doc/{id}`: reprocesar el mismo evento (reintentos, redelivery) **no duplica** el documento. Reintentos con *backoff* exponencial (3 intentos) y, agotados, **DLQ** (`failure_transport`).

**4. Adaptador sobre la API REST de ES** vía `symfony/http-client` (sin cliente dedicado, en línea con el health check). Índice creado de forma perezosa con *mapping* explícito (analizador `spanish` en `title`/`description`; `keyword`/`date` el resto) e indexado con `refresh=true` —aceptable porque corre en el worker, fuera del request—. La búsqueda fuerza el filtro `requester_id` para el Cliente (alcance por rol).

## Consecuencias

- (+) Search se despliega y evoluciona **sin tocar Ticketing**; el acoplamiento se limita a tres eventos inmutables. Deptrac sigue verde.
- (+) **Consistencia eventual correcta:** el documento se indexa con el estado ya commiteado; sin lecturas fantasma.
- (+) **Resiliencia:** reintentos + DLQ no pierden mensajes; la idempotencia tolera el reproceso (entrega *at-least-once* de RabbitMQ).
- (+) Sin dependencia nueva en `composer.json`: un adaptador HTTP delgado y testeable.
- (−) **No es un *transactional outbox* completo:** `DispatchAfterCurrentBus` cubre el orden commit→publish, pero si el proceso muere **entre** el commit y el envío al broker, ese evento se pierde. Se acepta para el alcance Must; el reindexado completo (`search:reindex`, HU-L3-E1-02, *Should*) es la red de seguridad para reconstruir el índice. **→ Resuelto en el [ADR 0008](0008-outbox-transaccional-ticketing.md):** los eventos de escritura de Ticketing pasan por un outbox transaccional y un relay; esta ventana queda cerrada.
- (−) `refresh=true` por escritura tiene coste en ES; es asumible al correr en el worker y a esta escala, no en un request caliente.
- (−) Releer la tabla `tickets` desde Search crea un **acoplamiento a nivel de esquema** (no de clases) con Ticketing: un cambio de columnas obliga a tocar el `TicketReadModel`. Trade-off consciente frente a denormalizar todo el payload en cada evento.

## Alternativas consideradas

- **Enriquecer los eventos con el payload completo del ticket** (título, descripción, etc.): el indexador no releería la BD. Descartada: engorda los eventos, duplica la verdad y obliga a versionar el contrato ante cada campo nuevo; además `TicketStatusChanged` tendría que cargar datos ajenos a su intención.
- **Transactional outbox** (tabla de eventos + relay que publica tras commit): elimina por completo la ventana de pérdida del punto (−). Pospuesta en su momento; **adoptada después en el [ADR 0008](0008-outbox-transaccional-ticketing.md)** para los eventos de escritura de Ticketing.
- **Cliente oficial `elasticsearch/elasticsearch`:** ergonómico, pero añade una dependencia pesada y su ciclo de versiones por un puñado de llamadas REST. Descartada por YAGNI.
- **Indexado síncrono en el caso de uso** (sin RabbitMQ): trivial, pero acopla la escritura a la disponibilidad de ES y bloquea el request. Contradice L4 (procesamiento en background).
