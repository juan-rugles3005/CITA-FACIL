# Plan de operación y mantenimiento de CitaFácil

Versión: 1 (2026-11-26). Está escrito para lo que existe hoy y marca lo que se hará cuando entren la base de datos y el login.

## 1. Qué se vigila

| Qué | Cómo | Señal de problema | Quién actúa |
|---|---|---|---|
| Que la aplicación responda | `GET /up` (HEALTHCHECK de Docker cada 30 s) | `docker ps` muestra `unhealthy` | Desarrollador |
| Errores de la aplicación | `storage/logs/laravel.log` y `docker logs citafacil` | Líneas `ERROR` o `CRITICAL` | Desarrollador |
| Pruebas y calidad | Pipeline de GitHub Actions en cada push | Ejecución en rojo | Quien hizo el cambio |
| Dependencias vulnerables | `composer audit` en el pipeline y Dependabot semanal | Pipeline en rojo o pull request de Dependabot | Desarrollador |
| Tiempo de la búsqueda de pacientes (RNF-01, 3 s) | Cuando exista la base de datos: prueba con 5.000 pacientes y cronómetro | Más de 3 segundos | Desarrollador |
| Sesión de 15 minutos sin actividad (RNF-02) | Cuando exista el login: prueba manual cada versión | La sesión no se cierra | Desarrollador |

## 2. Mantenimiento

- **Semanal:** revisar y probar los pull requests de Dependabot; si el pipeline sale en verde, se integran.
- **Por cambio:** nadie integra a `main` con el pipeline en rojo.
- **Cada mes:** reconstruir la imagen Docker para recibir los parches de la imagen base y volver a correr `composer audit`.
- **Registros:** no guardar datos personales en los logs (cédulas, nombres, teléfonos). Rotar `laravel.log` para que no crezca sin límite.

## 3. Respaldos (cuando exista PostgreSQL)

- Copia diaria de la base con `pg_dump`, guardada fuera del servidor, con las últimas 7 conservadas.
- Probar la restauración cada mes en un entorno de prueba; un respaldo que nunca se restauró no está comprobado.

## 4. Si algo falla

1. Mirar el pipeline y `docker logs citafacil`.
2. Si el fallo apareció con la última versión, volver a la imagen anterior (guía de despliegue, sección 3).
3. Anotar qué pasó y cómo se resolvió en `docs/incidentes.md`.

## 5. Límites conocidos

- Un solo servidor: si cae, no hay agenda (no se definió un requisito de disponibilidad; ver ADR-001).
- La IA no está en la operación: no hay modelos desplegados, por eso no hay monitoreo de deriva ni de calidad de un modelo. La IA se usó solo para desarrollar y revisar.
