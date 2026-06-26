# Architecture Decision Records (ADR)

Registro de decisiones de arquitectura. La **decisión** de cada ADR es inmutable: si cambia, se crea uno nuevo que *supersede* al anterior. Una consecuencia ya superada puede recibir un puntero al ADR que la resuelve.

| # | Decisión | Estado |
|---|---|---|
| [0001](0001-arquitectura-hexagonal.md) | Arquitectura hexagonal + DDD + monolito modular | Aceptada |
| [0002](0002-bounded-contexts.md) | Cuatro bounded contexts comunicados por eventos | Aceptada |
| [0003](0003-identificadores-uuid-v7.md) | Identificadores UUID v7 | Aceptada |
| [0004](0004-dominio-desacoplado-doctrine.md) | Dominio puro desacoplado de Doctrine | Aceptada |
| [0005](0005-cqrs-ligero.md) | CQRS ligero (comandos / consultas) | Aceptada |
| [0006](0006-refresh-token-en-cookie-httponly.md) | Refresh token en cookie HttpOnly con SameSite=Strict (sin token CSRF) | Aceptada |
| [0007](0007-indexado-asincrono-y-busqueda.md) | Indexado asíncrono y búsqueda desacoplada (Search ← eventos de Ticketing) | Aceptada |
| [0008](0008-outbox-transaccional-ticketing.md) | Outbox transaccional para los eventos de dominio de Ticketing | Aceptada |

**Formato:** Contexto · Decisión · Consecuencias · Alternativas consideradas.
