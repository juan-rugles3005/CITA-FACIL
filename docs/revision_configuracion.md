# Revisión del pipeline y del Dockerfile

Proyecto: CitaFácil (gestión de citas de un consultorio médico)
Estudiante: Juan José Rugeles Javela
Fecha: 2026-11-26

## 1. Cómo se generaron

Esta revisión tiene dos partes, y conviene no confundirlas:

- **Parte A (práctica):** los archivos `ci.yml` y `Dockerfile` del código de práctica de la semana 9 (API de ventas fiadas de Tienda La Esquina, en Python). El `LEEME.md` de ese código dice que los generó «un asistente de IA con un prompt simple»; no indica cuál. No son de CitaFácil: los usé porque no tenía un pipeline propio de la semana 9, como permite la actividad.
- **Parte B (CitaFácil):** el pipeline y el Dockerfile de este repositorio, que escribió Claude (chat) en la semana 10 y que revisé con la misma lista.

**Prompt del pipeline de práctica (parte A):** «Escribe un workflow de GitHub Actions para mi proyecto de Python con pytest».
**Prompt del Dockerfile de práctica (parte A):** «Escribe un Dockerfile para mi API de FastAPI».
**Prompt de la parte B:** ver la entrada 14 de `bitacora_ia.md`.

## 2. Hallazgos de la parte A (archivos de práctica, antes de corregirlos)

| Archivo y línea | Problema | Riesgo | Corrección |
|---|---|---|---|
| ci.yml:7 | Solo se ejecuta con `push` y a la rama `master`, pero la rama principal se llama `main` | El pipeline no corre nunca, o no corre en los pull requests, y nadie se entera | `on: push` y `pull_request` con `branches: [main]` |
| ci.yml:9 | `permissions: write-all` | Un paso comprometido podría modificar todo el repositorio | `permissions: contents: read` |
| ci.yml:11-12 | La clave está escrita en el archivo, a nivel de todo el workflow | Cualquiera con acceso de lectura la ve y queda en el historial | Guardarla en Settings → Secrets y usar `${{ secrets.CLAVE_RESPALDO }}` solo en el paso que la necesita |
| ci.yml:20 | `python-version: 3.10` sin comillas | YAML lo lee como el número 3.1 y se instala otra versión de Python | `python-version: '3.12'` entre comillas |
| ci.yml:22 | `pip install pytest fastapi httpx` sin versiones y sin usar `requirements-dev.txt` | La instalación cambia sin aviso, no coincide con lo que se probó en local y no corre ruff | `pip install -r requirements-dev.txt` y un paso `ruff check .` |
| ci.yml:26 | `continue-on-error: true` en las pruebas | Si una prueba falla, el pipeline sale en verde | Quitar la línea |
| Dockerfile:3 | `FROM python:latest` | La imagen cambia sin aviso y es muy pesada | `FROM python:3.12-slim` |
| Dockerfile:5 | `COPY . .` sin `.dockerignore` (el archivo que falta) | Copia a la imagen el `.env`, el historial de Git y la carpeta de pruebas | Crear `.dockerignore` con `.env`, `.git`, `tests` y `__pycache__` |
| Dockerfile:5-6 | Se copia todo el código antes de instalar las dependencias | Cada cambio de código reinstala todo y la construcción es lenta | Copiar primero `requirements.txt`, instalar y luego copiar el código |
| Dockerfile:7 | `ENV CLAVE_RESPALDO=...` | La clave queda dentro de la imagen y se ve con `docker history` | Pasarla al ejecutar: `docker run --env-file .env` |
| (sin línea) | No hay `USER`: el contenedor corre como root | Si vulneran la API, tienen root dentro del contenedor | Crear un usuario sin privilegios y usar `USER` |
| Dockerfile:9 | `--reload` en el comando | Es modo desarrollo: reinicia al ver cambios y gasta recursos | Quitar `--reload` |

La línea `--host 0.0.0.0` del Dockerfile **no es un problema**: dentro del contenedor hace falta para recibir peticiones desde fuera.

## 3. Lo que encontraron las herramientas (parte A)

| Herramienta | Avisos | ¿Coinciden con mis hallazgos? | ¿Qué no encontró? |
|---|---|---|---|
| actionlint | 0 | No encontró nada | Los 6 problemas del pipeline: rama equivocada, `write-all`, clave en el archivo, `3.10` sin comillas, dependencias sin fijar y `continue-on-error` |
| hadolint | 2 (DL3007 y DL3042) | Sí: `python:latest` y el `pip` sin `--no-cache-dir` | La clave en `ENV`, el usuario root, `--reload`, el `.dockerignore` que falta y el orden de las capas |

Conclusión: el pipeline pasa actionlint sin un solo aviso y sin embargo nunca correría en el repositorio y, si corriera, saldría en verde con las pruebas rotas. Los linters revisan la sintaxis, no la intención; esos errores solo se ven leyendo el archivo con una lista.

## 4. Revisión del pipeline y del Dockerfile de CitaFácil (parte B)

| Pregunta | `.github/workflows/ci.yml` y `Dockerfile` de CitaFácil |
|---|---|
| Eventos | `push` y `pull_request` hacia `main`, que es el nombre de la rama |
| Permisos | `contents: read` |
| Secretos | Ninguno en archivos. `.env` está en `.gitignore` y en `.dockerignore`. La `APP_KEY` de la prueba del contenedor se genera en el momento |
| Versiones | PHP `'8.2'` entre comillas; imagen `php:8.2-cli-alpine` y `composer:2.8`; dependencias fijadas por `composer.lock` |
| Dependencias | `composer install` desde el lock y `composer audit` falla si hay vulnerabilidades |
| ¿Un fallo hace fallar el pipeline? | Sí: no hay `continue-on-error`. Se comprueba en la sección 5 |
| Usuario del contenedor | `USER 10001`; el pipeline comprueba que no sea root |
| `.dockerignore` | Existe |
| Modo desarrollo | No hay `--reload`. Limitación: `php artisan serve` es el servidor de desarrollo de PHP; para un uso real hay que poner PHP-FPM con Nginx (ver `plan_operacion.md`) |

**Lo que encontró hadolint en el Dockerfile de CitaFácil:** 2 avisos antes del commit (DL3066, usuario no numérico, y DL3025, `HEALTHCHECK` sin notación JSON). Los corregí; la versión anterior no quedó en el historial de Git.

## 5. Verificación

- [ ] El pipeline corregido se ejecutó en GitHub y salió en verde: [PEGAR AQUÍ el enlace a la ejecución]
- [x] El pipeline salió en rojo cuando debía: [CI #1](https://github.com/juan-rugles3005/CITA-FACIL/actions/runs/45909817751) (commit df47a11) falló en el paso `composer audit` por 5 avisos de seguridad en `symfony/http-kernel`, `symfony/mailer` y `symfony/routing`, y las pruebas y la imagen Docker no corrieron. No hizo falta romper una prueba a propósito.
- [ ] La imagen se construye en el pipeline (trabajo «imagen-docker»).
- [x] No hay claves en `ci.yml`, en el `Dockerfile` ni en el historial de Git (revisado con `git log -p`).
