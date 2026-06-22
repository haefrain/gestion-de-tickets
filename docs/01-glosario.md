# Glosario — Sistema de Gestión de Tickets

**Prueba Técnica · IATSAE** · Fase **F0 · Fundaciones**

> Vocabulario común del proyecto (lenguaje ubicuo). Redactado en español; se conservan las siglas técnicas de uso universal. Mantener esta lista al día evita ambigüedades entre producto, backend y frontend.

## Dominio (negocio)

| Término | Definición |
|---|---|
| **Ticket** | Solicitud o incidencia reportada por un cliente, con ciclo de vida propio (estado, prioridad, responsable). Es el agregado central del dominio. |
| **Cliente** | Usuario que crea tickets y da seguimiento a los suyos. Rol `ROLE_CLIENT`. |
| **Agente** | Usuario de soporte que gestiona y resuelve tickets. Rol `ROLE_AGENT`. |
| **Administrador** | Usuario que configura el sistema y gestiona usuarios y roles. Rol `ROLE_ADMIN`. |
| **Estado** | Punto del ciclo de vida del ticket: `open`, `in_progress`, `resolved`, `closed`, `reopened`. |
| **Transición** | Cambio válido de un estado a otro; las inválidas se rechazan. |
| **Prioridad** | Urgencia del ticket: `low`, `medium`, `high`, `urgent`. |
| **Categoría** | Clasificación temática del ticket para enrutado y filtrado. |
| **Asignación** | Vínculo entre un ticket y el agente responsable de gestionarlo. |
| **Comentario** | Nota cronológica añadida a un ticket por un usuario autorizado. |
| **Historial** | Registro de auditoría de los cambios de estado y asignación del ticket. |
| **SLA** | *Service Level Agreement*: objetivo de tiempo de respuesta/resolución (versión sencilla en el MVP). |

## Arquitectura y backend

| Término | Definición |
|---|---|
| **Arquitectura hexagonal** | Estilo (Puertos y Adaptadores) que aísla el dominio de la infraestructura y el framework. |
| **Contexto delimitado** | *Bounded context*: frontera dentro de la cual un modelo y su lenguaje son consistentes (Ticketing, Identity, Search, Notifications). |
| **Caso de uso** | Operación de aplicación que orquesta el dominio para cumplir una intención (p. ej. "Crear ticket"). |
| **Puerto** | Interfaz que define una capacidad que el dominio necesita o expone, sin acoplarse a la tecnología. |
| **Adaptador** | Implementación concreta de un puerto (p. ej. repositorio Doctrine, cliente de Elasticsearch). |
| **Entidad** | Objeto del dominio con identidad propia y ciclo de vida (p. ej. Ticket). |
| **Value Object** | Objeto sin identidad, definido por sus valores e inmutable (p. ej. Prioridad). |
| **Agregado** | Conjunto de objetos del dominio tratado como una unidad de consistencia. |
| **Evento de dominio** | Hecho relevante ocurrido en el dominio (`TicketCreated`, `TicketStatusChanged`, `TicketAssigned`). |
| **Repositorio** | Puerto que abstrae la persistencia y recuperación de agregados. |
| **CQRS** | Separación de modelos de lectura y escritura cuando aporta (p. ej. búsqueda). |

## Plataforma e infraestructura

| Término | Definición |
|---|---|
| **JWT** | *JSON Web Token*: token firmado que transporta la identidad y autoriza las peticiones a la API. |
| **RBAC** | *Role-Based Access Control*: control de acceso basado en roles. |
| **Caché** | Almacén temporal rápido (Redis) para acelerar lecturas frecuentes. |
| **TTL** | *Time To Live*: tiempo de vida de una entrada en caché antes de expirar. |
| **Invalidación** | Eliminación de entradas de caché obsoletas tras una escritura. |
| **Cola** | Canal de mensajes en RabbitMQ por donde viajan los eventos a procesar. |
| **Trabajador** | *Worker*: proceso que consume mensajes de la cola y los procesa fuera del request. |
| **DLQ** | *Dead-Letter Queue*: cola donde acaban los mensajes que fallan tras varios reintentos. |
| **Idempotencia** | Propiedad por la que procesar un mensaje varias veces produce el mismo efecto que procesarlo una. |
| **Índice** | Estructura de Elasticsearch que almacena documentos para búsqueda eficiente. |
| **Mapping** | Definición de los campos y tipos de un índice de Elasticsearch. |
| **Reindexado** | Reconstrucción del índice de búsqueda a partir de la base de datos. |

## Producto y proceso ágil

| Término | Definición |
|---|---|
| **Legenda** | Iniciativa de alto nivel que agrupa épicas (nivel superior de la jerarquía). |
| **Épica** | Conjunto de historias de usuario relacionadas dentro de una legenda. |
| **Historia de Usuario (HU)** | Necesidad descrita como "Como _rol_ quiero _acción_ para _beneficio_", con criterios de aceptación. |
| **Criterio de aceptación** | Condición verificable que determina que una HU está correctamente implementada. |
| **MoSCoW** | Esquema de priorización: *Must*, *Should*, *Could*. |
| **DoR** | *Definition of Ready*: condiciones para que una HU pueda empezar a desarrollarse. |
| **DoD** | *Definition of Done*: condiciones para considerar una HU terminada. |
| **Story Point** | Unidad relativa de esfuerzo (escala Fibonacci) usada para estimar HU. |
| **ADR** | *Architecture Decision Record*: registro breve de una decisión de arquitectura y sus consecuencias. |
