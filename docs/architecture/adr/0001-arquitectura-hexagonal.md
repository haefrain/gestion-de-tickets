# ADR 0001 · Arquitectura hexagonal + DDD + monolito modular

- **Estado:** Aceptada · 2026-06-22

## Contexto

La prueba valora un backend sólido con SOLID y arquitectura hexagonal. Necesitamos un estilo que aísle el dominio, facilite los tests y permita evolucionar sin acoplarse a Symfony/Doctrine.

## Decisión

Adoptar **arquitectura hexagonal** (Puertos y Adaptadores) con **DDD táctico**, organizada como **monolito modular**: cada bounded context tiene capas `Domain`, `Application` e `Infrastructure`, con dependencias apuntando hacia el dominio.

## Consecuencias

- (+) Dominio testeable sin infraestructura; framework reemplazable.
- (+) Límites claros entre contextos; base para extraer servicios si hiciera falta.
- (−) Más ceremonia (puertos, mapeos) que un CRUD acoplado.

## Alternativas consideradas

- **MVC clásico acoplado a Doctrine:** más rápido de escribir, pero no demuestra las competencias que pide la prueba.
- **Microservicios:** sobredimensionado para el alcance y el plazo.
