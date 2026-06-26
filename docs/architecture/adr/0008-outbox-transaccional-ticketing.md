# ADR 0008 · Outbox transaccional para los eventos de dominio de Ticketing

- **Estado:** Aceptada · 2026-06-25
- **Relacionada:** sustituye el mecanismo de publicación descrito en el ADR 0007 (punto 2) para los casos de uso de escritura de Ticketing.

## Contexto

El ADR 0007 difiere el envío de eventos al broker con `DispatchAfterCurrentBusStamp`: el `MessengerEventBus` retiene el envío hasta que el `doctrine_transaction` del `command.bus` confirma, de modo que el worker nunca consume un ticket sin commitear. Pero ese mismo ADR reconoce una ventana abierta entre sus consecuencias:

> *No es un transactional outbox completo: `DispatchAfterCurrentBus` cubre el orden commit→publish, pero si el proceso muere **entre** el commit y el envío al broker, ese evento se pierde.*

Es la avería clásica del **dual-write**: PostgreSQL (estado) y RabbitMQ (evento) son dos sistemas distintos sin transacción común. Entre el `COMMIT` y el `basic.publish` al broker hay un instante en el que un fallo de proceso (deploy, OOM, corte) deja el ticket guardado pero su evento jamás emitido; Search no reindexa y Notifications no avisa, sin rastro del fallo.

Hay que cerrar esa ventana **sin** acoplar el dominio a la infraestructura ni romper la regla hexagonal.

## Decisión

Implementar un **outbox transaccional** para los eventos de dominio de los casos de uso de escritura de Ticketing.

**1. Un puerto de aplicación, `EventOutbox`,** reemplaza a `EventBus` en los cinco handlers de escritura (`CreateTicket`, `ChangeTicketStatus`, `AssignTicket`, `UpdateTicket`, `AddComment`). En vez de publicar, **registran** los eventos: `$this->outbox->add(...$ticket->pullDomainEvents())`.

**2. El adaptador `DoctrineEventOutbox` escribe por DBAL en la misma conexión** que persiste el agregado. Como el caso de uso corre dentro del `doctrine_transaction` del `command.bus`, el `INSERT` en `ticketing_outbox` cae en la **misma transacción** que el `save()` del ticket: o se confirman ambos, o se revierten ambos. **Atomicidad real**, sin coordinador de dos fases. La (de)serialización evento ↔ fila vive en `TicketingEventSerializer` (Infrastructure), coherente con el ADR 0004: el dominio se mantiene puro.

**3. Un relay independiente publica.** El comando `ticketing:outbox:relay` (servicio `outbox-relay` en Compose) sondea los pendientes y, por lote dentro de una transacción:

```
SELECT ... WHERE published_at IS NULL ORDER BY created_at LIMIT N FOR UPDATE SKIP LOCKED
  → por fila: event.bus.dispatch(evento, [OutboxIdStamp(id)])   (routing → async_events → RabbitMQ)
  → UPDATE ticketing_outbox SET published_at = now()
```

`FOR UPDATE SKIP LOCKED` permite escalar a **varios relays** sin que dos tomen la misma fila. El relay reutiliza el `event.bus` y su routing existentes: para los consumidores (Search, Notifications) nada cambia.

**4. Idempotencia del consumo con `processed_messages`.** Cada fila del outbox lleva un `id` (UUID v7) que viaja al consumidor como `OutboxIdStamp`. El `IdempotentMessageMiddleware` (en `event.bus`, sólo activo al consumir, detectado por `ReceivedStamp`) descarta el mensaje si su `id` ya está en `processed_messages`; si es nuevo, deja procesar y lo registra. Así una **entrega at-least-once** (reintentos de RabbitMQ, o re-publicación del relay tras un crash a mitad de lote) se convierte en **efecto effectively-once**.

El esquema (migración `Version20260625000001`):

| `ticketing_outbox` | | `processed_messages` | |
|---|---|---|---|
| `id` UUID PK | identidad del mensaje | `message_id` UUID PK | dedupe |
| `aggregate_id` UUID | ticket | `processed_at` timestamptz | |
| `event_name` varchar | nombre estable | | |
| `payload` jsonb | datos del evento | | |
| `occurred_on` timestamptz | | | |
| `created_at` timestamptz | | | |
| `published_at` timestamptz NULL | marca de envío | | |

Índice parcial `idx_outbox_unpublished (created_at) WHERE published_at IS NULL`: el relay sólo recorre pendientes.

## Alcance y límites

- Cubre los **cinco casos de uso de escritura de Ticketing** (los productores que corren dentro del `doctrine_transaction` del `command.bus`).
- Queda **fuera** la re-emisión del lado consumidor: `AutoAssignOnCreateHandler` reacciona a `TicketCreated` y emite `TicketAssigned` desde el worker, donde no hay transacción de comando que envolver; sigue usando `EventBus`. Llevarlo al outbox exigiría envolver el `event.bus` en una transacción, lo que arrastraría a los consumidores que envían correo (no transaccionable). Se documenta como límite consciente; el reindexado completo (HU-L3-E1-02) sigue siendo la red de seguridad para reconstruir el estado derivado.
- `RegisterUserHandler` (Identity) conserva `EventBus`: este ADR acota Ticketing.

## Consecuencias

- (+) **Cierra la ventana de pérdida** del ADR 0007: agregado y evento se confirman atómicamente; ningún evento de Ticketing se pierde entre commit y publicación.
- (+) **Resiliencia ante caída del broker o de Elasticsearch:** si RabbitMQ no acepta el envío, el relay revierte y los eventos se acumulan en `ticketing_outbox` hasta que vuelva; nada se pierde y se publica en orden al recuperarse.
- (+) **Dominio intacto:** los handlers dependen de un puerto; la serialización y el SQL viven en Infrastructure. Deptrac sigue en verde (0 violaciones).
- (+) **Idempotencia explícita** además del upsert de ES: protege también a consumidores no idempotentes por naturaleza (p. ej. Notifications) frente a la entrega duplicada.
- (−) **Más piezas a operar:** una tabla, un proceso relay y la `processed_messages` (que conviene podar periódicamente). El comando `--time-limit` recicla el proceso (anti memory-leak), igual que el worker.
- (−) **Latencia adicional** de publicación igual al intervalo de sondeo del relay (`--sleep`, 1 s por defecto). Asumible: el indexado ya es asíncrono.
- (−) **Dedupe a nivel de mensaje** (no por handler): si un mensaje tiene varios consumidores y uno falla tras que otro tuvo éxito, el reintento se salta el mensaje completo. Aceptable porque los consumidores actuales son idempotentes (upsert en ES) o tolerantes; la variante `(message_id, handler)` queda anotada como refinamiento futuro.

## Alternativas consideradas

- **Seguir solo con `DispatchAfterCurrentBusStamp`** (ADR 0007): más simple, pero deja la ventana de pérdida abierta. Era el alcance Must; este ADR la cierra.
- **Publicar el `Envelope` serializado de Messenger** en el outbox en vez de los datos del evento: ataría la tabla al formato de cable de Messenger. Se prefiere un `event_name` estable + `payload` JSON, desacoplado de la librería y legible.
- **Listener `postCommit` de Doctrine** que publique tras confirmar: no resuelve el dual-write (el proceso puede morir igualmente entre el commit y el publish del listener) y mete infraestructura de mensajería en el ciclo del ORM.
- **CDC / Debezium sobre el WAL de PostgreSQL:** outbox sin código de relay, pero exige Kafka Connect y operación nueva; desproporcionado para el alcance.
- **Marcar `published_at` antes de publicar** (at-most-once): evitaría duplicados pero reintroduce pérdida si el publish falla tras marcar. Se prefiere at-least-once + idempotencia, que no pierde.
