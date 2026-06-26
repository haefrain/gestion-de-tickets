# Visión del Producto — Sistema de Gestión de Tickets

**Prueba Técnica · IATSAE** · Fase **F0 · Fundaciones**

| Campo | Valor |
|---|---|
| **Estado** | Estable · v1.0 |
| **Fecha** | 2026-06-22 |
| **Documentos relacionados** | [`ROADMAP.md`](../ROADMAP.md) · [`docs/product/backlog.md`](product/backlog.md) · [`docs/01-glosario.md`](01-glosario.md) |

## 1. El problema

Los equipos de soporte gestionan incidencias por canales dispersos (correo, chat, llamadas) sin trazabilidad ni priorización claras. Esto provoca tiempos de respuesta altos, pérdida de solicitudes y falta de visibilidad sobre la carga de trabajo. Se necesita un punto único donde los clientes reporten incidencias y los agentes las gestionen con estado, prioridad y asignación.

## 2. Visión

> Una plataforma de tickets simple y rápida donde el cliente reporta y sigue sus incidencias, y el equipo de soporte las prioriza, asigna y resuelve con trazabilidad completa — apoyada en una arquitectura backend sólida (hexagonal, asíncrona y con búsqueda) que demuestre buenas prácticas de ingeniería.

El backend es el protagonista; el frontend es una capa de apoyo que prueba la integración end-to-end.

## 3. Objetivos

**De producto**

- Centralizar el ciclo de vida del ticket: creación, clasificación, asignación, resolución y cierre.
- Dar visibilidad por rol: el cliente ve lo suyo; el agente y el admin ven y gestionan todo.
- Reducir el tiempo hasta la primera respuesta mediante notificaciones y búsqueda eficiente.

**Técnicos**

- Demostrar arquitectura hexagonal + SOLID con dominio aislado del framework.
- Ejercitar el stack completo: PostgreSQL, Redis (cache), RabbitMQ (async), Elasticsearch (búsqueda) y JWT.
- Garantizar reproducibilidad total con Docker y calidad automatizada (linters estrictos + tests + CI).

## 4. Métricas de éxito (KPIs)

El sistema debe permitir **medir y soportar** estos indicadores (no son datos reales, sino objetivos de diseño).

| KPI | Descripción | Objetivo de diseño |
|---|---|---|
| Tiempo a primera respuesta (FRT) | Desde creación hasta primer cambio/asignación | Visible y medible por ticket |
| Tiempo de resolución (TTR) | Desde creación hasta `resolved` | Trazado por historial |
| Tasa de resolución | % de tickets `resolved`/`closed` sobre el total | Consultable por filtros |
| Backlog abierto | Tickets en `open`/`in_progress` | Listable y buscable |
| Latencia API (p95) | Lectura de listados/detalle | < 200 ms con cache caliente |
| Tiempo de procesamiento async | Desde evento hasta notificación | Segundos, fuera del request |

## 5. Personas

| Persona | Rol | Objetivo principal | Necesidad clave |
|---|---|---|---|
| **Cliente** | `ROLE_CLIENT` | Resolver su incidencia rápido | Crear tickets y seguir su estado |
| **Agente** | `ROLE_AGENT` | Gestionar y resolver con eficiencia | Priorizar, asignarse y buscar tickets |
| **Administrador** | `ROLE_ADMIN` | Operar y configurar el sistema | Gestionar usuarios, roles y supervisión |

## 6. Alcance

**Incluido en el MVP (núcleo)**

- Autenticación JWT y autorización por roles (RBAC).
- CRUD de tickets con estados, prioridad, categoría y asignación.
- Comentarios e historial/auditoría.
- Búsqueda y filtros con Elasticsearch.
- Notificaciones y procesamiento en background con RabbitMQ.
- Cache de lecturas con Redis.
- Frontend React (auth + gestión de tickets).
- Entorno reproducible con Docker.

**Incluido pero en versión sencilla** *(todo, pero simple)*

- **Notificaciones por email:** **SMTP real** vía Symfony Mailer (DSN en `.env`; en local puede usarse un sandbox tipo Mailtrap).
- **Adjuntos:** subida básica de archivos al ticket.
- **SLA:** campo de objetivo de tiempo + alerta simple, sin motor de escalado complejo.

**Fuera del MVP (evolución futura)**

- Multi-tenant / multi-organización *(la arquitectura hexagonal lo deja preparado, no implementado)*.
- Reportes y analítica avanzada.
- Integraciones externas (Slack, email real, base de conocimiento).
- Auto-asignación inteligente por carga/ML.

## 7. Casos de uso principales

Resumen; el detalle vive en [`docs/product/backlog.md`](product/backlog.md).

1. El **cliente** se registra, inicia sesión y crea un ticket.
2. El **agente** busca, prioriza, se asigna y cambia el estado del ticket.
3. El sistema **notifica** de forma asíncrona los cambios relevantes.
4. El **cliente** consulta el estado y comenta en su ticket.
5. El **admin** gestiona usuarios y supervisa la operación.

## 8. Riesgos y mitigaciones

| Riesgo | Impacto | Mitigación |
|---|---|---|
| Complejidad del stack (Redis + RabbitMQ + ES) en tiempo acotado | Alto | Docker Compose listo + alcance "sencillo" + priorización MoSCoW |
| Sincronización PostgreSQL ↔ Elasticsearch | Medio | Indexado asíncrono + comando de reindexado idempotente |
| Sobre-ingeniería de la arquitectura hexagonal | Medio | Pragmatismo: aplicar patrones solo donde aporten (ver F3) |
| Inconsistencias de cache | Medio | Invalidación explícita en escrituras + TTL |

## 9. Definición de éxito de la prueba

La entrega es exitosa si el sistema arranca con un comando en cualquier máquina, ejercita todo el stack obligatorio con código limpio y probado, y la documentación permite entender las decisiones de diseño sin leer todo el código.
