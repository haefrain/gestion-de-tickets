# ADR 0003 · Identificadores UUID v7

- **Estado:** Aceptada · 2026-06-22

## Contexto

Necesitamos identificadores únicos generables en la aplicación (no autoincrementales) para no exponer conteos ni acoplar IDs a la base de datos. Deben rendir bien en índices de PostgreSQL.

## Decisión

Usar **UUID v7** vía `symfony/uid`. Son ordenables por tiempo (timestamp en los bits altos) y se almacenan en el tipo `uuid` nativo de PostgreSQL.

## Consecuencias

- (+) Inserciones casi secuenciales → menos fragmentación de índices que UUID v4.
- (+) Generables en el dominio sin ida y vuelta a la BD; tipo nativo en PostgreSQL.
- (−) Menos compactos que un entero o un ULID en texto (36 chars).

## Alternativas consideradas

- **ULID:** mismo beneficio temporal y más compacto (26 chars), pero sin tipo nativo en PostgreSQL.
- **UUID v4:** aleatorio puro; peor rendimiento en índices.
- **Autoincremental:** expone volumen y acopla el ID a la persistencia.
