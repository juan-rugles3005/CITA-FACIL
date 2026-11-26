# Guía de despliegue de CitaFácil

Versión: 1 (2026-11-26). Cubre lo que existe hoy: la lógica de la agenda y la página de inicio de Laravel. La base de datos PostgreSQL, el login y las pantallas aún no están construidos (ver el informe final, sección 5).

## 1. Requisitos del servidor

- Docker 24 o superior (o PHP 8.2 con Composer 2 si no se usa Docker).
- Un valor de `APP_KEY` propio, generado con `php artisan key:generate --show`. No se reutiliza el de otro entorno ni se escribe en el repositorio.

## 2. Despliegue con Docker

1. Clonar el repositorio y entrar a la carpeta.
2. Generar la clave: `docker run --rm php:8.2-cli-alpine php -r "echo 'base64:'.base64_encode(random_bytes(32));"`.
3. Crear un archivo `.env.produccion` en el servidor, fuera de Git, con:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=<la clave del paso 2>
   SESSION_DRIVER=file
   CACHE_STORE=file
   ```
4. Construir: `docker build -t citafacil:1.0 .`
5. Ejecutar: `docker run -d --name citafacil -p 8000:8000 --env-file .env.produccion --restart unless-stopped citafacil:1.0`
6. Comprobar: `curl http://localhost:8000/up` debe responder 200, y `docker ps` debe mostrar el estado `healthy`.

## 3. Actualizar a una versión nueva

1. El pipeline debe estar en verde en `main`.
2. `git pull`, construir con una etiqueta nueva (`citafacil:1.1`) y ejecutar el contenedor nuevo.
3. Si `/up` falla, volver a la imagen anterior: detener el contenedor nuevo y ejecutar de nuevo `citafacil:1.0`. Por eso no se borra la imagen anterior hasta confirmar que la nueva funciona.

## 4. Antes de publicar con datos reales

- Poner PHP-FPM con Nginx y HTTPS delante: `php artisan serve` es solo para desarrollo.
- Conectar PostgreSQL con un usuario propio de la aplicación (no el administrador) y la contraseña solo en el `.env.produccion`.
- Revisar la Ley 1581 de 2012: autorización de los pacientes, tratamiento y acceso a sus datos.
