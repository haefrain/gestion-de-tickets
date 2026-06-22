-- GlitchTip comparte la instancia de PostgreSQL con su propia base de datos.
-- Este script se ejecuta una sola vez, al inicializar el volumen de Postgres.
SELECT 'CREATE DATABASE glitchtip'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'glitchtip')\gexec
