# Imagen de CitaFacil (Laravel 12, PHP 8.2).
# Etapa 1: instala las dependencias de produccion (sin herramientas de desarrollo).
FROM php:8.6-cli-alpine AS dependencias
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
WORKDIR /app
# Primero solo los archivos de dependencias, para aprovechar la cache de capas.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist \
    --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --no-dev --optimize

# Etapa 2: imagen final, sin Composer y sin el codigo de desarrollo.
FROM php:8.6-cli-alpine
# Usuario sin privilegios: si alguien vulnera la aplicacion, no es root.
RUN addgroup -S -g 10001 app && adduser -S -u 10001 -G app app
WORKDIR /app
COPY --from=dependencias --chown=app:app /app /app
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && chown -R app:app storage bootstrap/cache
USER 10001
EXPOSE 8000
# La clave APP_KEY y cualquier secreto se pasan al ejecutar el contenedor (-e o --env-file); no estan en la imagen.
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s \
    CMD ["wget", "-q", "-O", "/dev/null", "http://127.0.0.1:8000/up"]
# --host 0.0.0.0 es necesario dentro del contenedor para recibir peticiones desde fuera.
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
