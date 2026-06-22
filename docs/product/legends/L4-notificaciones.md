# L4 · Notificaciones y Asincronía

**Backlog rico** · Bounded context **Notifications** · [↩ índice](../backlog.md)

> Procesamiento en background con RabbitMQ (Symfony Messenger): publicación de eventos, workers resilientes y notificaciones. Puerto `EventPublisher`; colas `indexing` y `notifications` con DLQ.

---

## Épica L4-E1 · Procesamiento en background

### HU-L4-E1-01 · Publicar eventos a la cola

**Narrativa:** Como **sistema** quiero **publicar los eventos de dominio en RabbitMQ** para **procesarlos fuera del request**.

| Metadato | Valor |
|---|---|
| **Contexto** | Notifications · **Épica:** L4-E1 |
| **Prioridad** | Must · **Estimación:** 5 · **Labels:** `area:async`, `type:story` |
| **Depende de** | — |

**Criterios de aceptación**

```gherkin
Escenario: Publicación asíncrona
  Dado que ocurre TicketCreated/StatusChanged/Assigned
  Cuando el caso de uso finaliza
  Entonces el evento se despacha a RabbitMQ
  Y el request HTTP responde sin esperar al procesamiento
```

**Diseño técnico:** puerto `EventPublisher::publish(DomainEvent)`; adaptador con **Symfony Messenger** (transporte AMQP); los eventos se despachan tras confirmar la escritura.

**Infraestructura:** **RabbitMQ** (exchange `tickets` → colas `indexing`, `notifications`).

**Tests** · *Integración:* tras el caso de uso, el mensaje está en la cola; el request no bloquea.

---

### HU-L4-E1-02 · Worker con reintentos y DLQ

**Narrativa:** Como **equipo** quiero **un worker resiliente** para **no perder mensajes ante fallos**.

| Metadato | Valor |
|---|---|
| **Contexto** | Notifications · **Épica:** L4-E1 |
| **Prioridad** | Must · **Estimación:** 5 · **Labels:** `area:async`, `type:story` |
| **Depende de** | HU-L4-E1-01 |

**Criterios de aceptación**

```gherkin
Escenario: Reintentos con backoff
  Dado un consumidor que falla de forma transitoria
  Cuando procesa un mensaje
  Entonces reintenta con backoff (p. ej. 3 intentos)

Escenario: Dead-letter
  Dado que se agotan los reintentos
  Entonces el mensaje pasa a la DLQ correspondiente

Escenario: Idempotencia
  Dado el mismo mensaje procesado dos veces
  Entonces no se duplican los efectos
```

**Diseño técnico:** política de reintentos de Messenger (backoff exponencial); DLQ por cola; idempotencia por `TicketId`.

**Infraestructura:** **RabbitMQ** (`dlq:indexing`, `dlq:notifications`); ejecución con `messenger:consume`.

**Tests** · *Integración:* fallo transitorio reintenta; fallo persistente va a DLQ; reproceso no duplica.

---

## Épica L4-E2 · Notificaciones

### HU-L4-E2-01 · Notificar asignación al agente

**Narrativa:** Como **Agente** quiero **ser notificado al asignarme un ticket** para **atenderlo**.

| Metadato | Valor |
|---|---|
| **Contexto** | Notifications · **Épica:** L4-E2 |
| **Prioridad** | Should · **Estimación:** 3 · **Labels:** `area:async`, `type:story` |
| **Depende de** | HU-L4-E1-02, HU-L2-E2-02 |

**Criterios de aceptación**

```gherkin
Escenario: Notificación de asignación
  Dado que se consume TicketAssigned
  Cuando el worker lo procesa
  Entonces el agente recibe una notificación (email vía SMTP + registro en BD)
```

**Diseño técnico:** consumidor `NotifyAssignmentHandler`; puerto `Notifier::send()`; canal email por **SMTP real** (Symfony Mailer, DSN en `.env`).

**Infraestructura:** **RabbitMQ** (`notifications`); **PostgreSQL** (`notifications` log).

**Tests** · *Integración:* al consumir `TicketAssigned`, se registra la notificación al agente.

---

### HU-L4-E2-02 · Notificar cambio de estado al cliente

**Narrativa:** Como **Cliente** quiero **enterarme de cambios en mi ticket** para **estar informado**.

| Metadato | Valor |
|---|---|
| **Contexto** | Notifications · **Épica:** L4-E2 |
| **Prioridad** | Should · **Estimación:** 3 · **Labels:** `area:async`, `type:story` |
| **Depende de** | HU-L4-E1-02 |

**Criterios de aceptación**

```gherkin
Escenario: Notificación de cambio de estado
  Dado que se consume TicketStatusChanged
  Cuando el worker lo procesa
  Entonces el cliente dueño recibe una notificación
```

**Diseño técnico:** consumidor `NotifyStatusChangeHandler`; reutiliza el puerto `Notifier`.

**Infraestructura:** **RabbitMQ** (`notifications`); **PostgreSQL** (log de notificaciones).

**Tests** · *Integración:* al consumir `TicketStatusChanged`, el cliente recibe notificación.
