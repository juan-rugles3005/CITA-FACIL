# Informe de revisión de código y seguridad

Proyecto: CitaFácil (gestión de citas de un consultorio médico, Laravel 12)
Estudiante: Juan José Rugeles Javela
Fecha: 2026-11-25
Commit revisado: 42a6a83 (antes de las correcciones; las correcciones están en la rama `semana-6-revision`)

## 1. Herramientas y criterios

- **Linter:** PHP_CodeSniffer 3.13.2 con PSR-12 y reglas de seguridad (`Generic.PHP.ForbiddenFunctions` para `eval`, `exec`, `system`, `shell_exec`, `unserialize`, `md5` y `sha1`, además de `Squiz.PHP.Eval`), PHPStan 2.1.17 nivel 8 y Laravel Pint. Configuración en `phpcs.xml`, `phpstan.neon` y `pint.json`; se corren con `composer lint`. (En PHP no existe `pyproject.toml` ni `ruff`; estos son los equivalentes.)
- **Auditoría de dependencias:** `composer audit` (equivalente a pip-audit) — resultado: **[PEGAR AQUÍ la salida de `composer audit` ejecutada en tu computador]**.
- **Lista de revisión:** la de la semana 6 (corrección y pruebas, diseño y legibilidad, seguridad).
- **Revisión con IA:** Claude (chat) con el prompt de revisión de la semana (ver sección 3 y entrada 11 de la bitácora).

### Puntos de seguridad revisados

| Punto | ¿Aplica? | Resultado |
|---|---|---|
| Inyección (consultas con parámetros) | Parcial: no hay consultas SQL (las citas están en memoria, sin base de datos ni Eloquent en uso) | Ninguna consulta armada con texto. Pero `registrarCita` aceptaba cualquier texto, que sería peligroso al conectar PostgreSQL: H-01 |
| Secretos (código, historial, .gitignore, .env.example) | Sí | `.env` está en `.gitignore` y `.env.example` existe sin claves. Revisé el historial completo con `git log -p`: no hay claves ni contraseñas. Hallazgo de configuración: H-02 |
| Contraseñas (scrypt, bcrypt o argon2 con sal) | Parcial: no hay registro ni login propios; solo el modelo `User` de Laravel | `User` usa el cast `hashed` (Hash de Laravel: bcrypt con sal, `BCRYPT_ROUNDS=12`); no hay MD5 ni SHA-1. Hallazgo: el seeder crea un usuario con la contraseña conocida «password» (H-03) |
| Control de acceso (rol verificado y prueba de abuso) | No aplica todavía: `routes/web.php` solo tiene la ruta pública `/`; no hay usuarios, roles ni operaciones restringidas | Se hace en la entrega que agregue login y roles (ver pendientes). Sí agregué pruebas de abuso para las entradas (H-01) |
| Dependencias (existen, necesarias, versión fijada, composer audit) | Sí | `composer.lock` fija las versiones. `laravel/sail` no se usa (Docker). `composer audit`: ver arriba |

## 2. Tabla de hallazgos

(Líneas del commit 42a6a83.)

| Id | Archivo y línea | Problema | Categoría (OWASP o tipo) | Severidad | Corrección | Commit |
|---|---|---|---|---|---|---|
| H-01 | app/Agenda/Agenda.php:15-30 | `registrarCita` guarda cualquier cédula y nombre de médico: texto vacío, fragmentos de SQL o HTML, saltos de línea, textos enormes | A03 Inyección / A04 Diseño inseguro | Media (alta cuando se conecte la base de datos) | Cédula de 6 a 10 dígitos; médico no vacío, máximo 100 caracteres y sin caracteres de control; 15 pruebas de abuso nuevas (14 fallan en 42a6a83 y pasan con la corrección) | 3f1601d |
| H-02 | .env.example:4 | `APP_DEBUG=true` por defecto: si alguien copia el archivo a producción, los errores muestran rutas, consultas y variables | A05 Configuración insegura | Media | `APP_DEBUG=false` con comentario; también nombre y idioma del proyecto | b8b9c21 |
| H-03 | database/seeders/DatabaseSeeder.php:20-23 y database/factories/UserFactory.php:31 | El seeder crea `test@example.com` con la contraseña conocida «password» en cualquier entorno | A07 Fallas de identificación y autenticación | Media | El seeder solo crea ese usuario en `local` y `testing` | b8b9c21 |
| H-04 | app/Agenda/Agenda.php:34-35 | Línea de 133 caracteres y valor mágico `'00.000000'` | Mantenibilidad (valor mágico) | Baja | Constante `SEGUNDOS_EXACTOS` y condiciones con nombre | 7cb696b |
| H-05 | app/Agenda/Agenda.php:54 y HorarioOcupadoException.php:9 | Sin salto de línea al final del archivo (PSR-12) | Estilo | Baja | Se agregó el salto de línea | bfaec93 |
| H-06 | app/Agenda/Cita.php:11-13, 34; app/Models/User.php:14 | Constantes sin separación, `!` sin espacio, dos traits en un solo `use` | Estilo | Baja | Corregido con Pint y a mano | bfaec93 |
| H-07 | (todo el proyecto) | No había linter configurado | Proceso | Baja | `phpcs.xml`, `phpstan.neon`, `pint.json` y `composer lint` | 577035a |
| H-08 | app/Agenda/Agenda.php y Cita.php | Las funciones principales no tenían docstring completo (qué recibe, devuelve y lanza) | Legibilidad | Baja | Docstrings en `registrarCita`, `estaOcupado`, `totalCitas`, constructor de `Cita` y las clases | 3a88e40 |
| H-09 | README.md | Era el README por defecto de Laravel; no explicaba el proyecto ni cómo instalarlo | Documentación | Baja | README con las ocho secciones | d00b4ee |

**Severidad:** alta (permite acceder o cambiar datos sin permiso, o expone secretos), media (error probable o riesgo con condiciones), baja (estilo o mantenibilidad).

Resultado final: 71 pruebas en verde (las 56 anteriores sin modificar y 15 nuevas), PHPCS, PHPStan nivel 8 y Pint sin avisos.

### Avisos del linter no corregidos

| Regla | Archivo y línea | Por qué se mantiene |
|---|---|---|
| PSR1.Methods.CamelCapsMethodName (20 avisos) | tests/Unit y tests/Feature | Las pruebas se llaman `test_algo_en_snake_case`; renombrarlas sería modificar las pruebas, y la regla de la tarea lo prohíbe. PHPCS excluye `tests/` para esta regla |
| Generic.Files.LineLength (9 avisos) | tests/Unit/AgendaRegistrarCitaReglasTest.php y otras | Líneas largas con las descripciones de los casos de prueba; PHPCS excluye `tests/` para esta regla |
| PHPStan missingType.iterableValue y alreadyNarrowedType (12 avisos) | tests/ | Providers de PHPUnit sin tipo de arreglo y la prueba de ejemplo de Laravel (`assertTrue(true)`); PHPStan analiza solo `app/` |
| Pint (varias reglas de estilo) | tests/ | Pint renombraría métodos de prueba; `pint.json` no revisa `tests/` |

## 3. Revisión con IA

**Prompt usado:** «Revisa app/Agenda, routes, database, config y .env.example de CitaFácil con estos criterios: corrección y pruebas, diseño y legibilidad, y seguridad (inyección, secretos, contraseñas, control de acceso, dependencias). Dame una tabla de hallazgos con archivo, línea y severidad, y separa los problemas seguros de las sospechas.» Ver entrada 11 de la bitácora.

| Hallazgo de la IA | ¿Real o falso positivo? | Cómo lo verifiqué |
|---|---|---|
| Seguro: `registrarCita` no valida cédula ni médico (Agenda.php:15-30) | Real (H-01) | Con el código original, `registrarCita("1' OR '1'='1", "<script>", ...)` devuelve una `Cita` sin error. Las pruebas de abuso fallan en 42a6a83 y pasan después de 3f1601d |
| Seguro: `APP_DEBUG=true` en `.env.example` | Real (H-02) | Línea 4 del archivo en 42a6a83 |
| Seguro: el seeder crea un usuario con contraseña «password» | Real (H-03) | `UserFactory.php:31` usa `Hash::make('password')` y el seeder no revisa el entorno |
| Seguro: línea larga y valor mágico `'00.000000'` | Real (H-04) | PHPCS reporta la línea; el valor aparece en la condición |
| Sospecha: `==` en `estaOcupado` (línea 42) podría comparar mal las fechas | Falso positivo | `==` entre `DateTimeImmutable` compara el instante: probé que 09:00 de Bogotá es igual a 14:00 UTC. Es lo que se necesita (ver entrada 3 de la bitácora) |
| Sospecha: carrera entre revisar y guardar en `registrarCita` | Falso positivo hoy | Las citas están en memoria y PHP atiende una petición a la vez, así que no hay carrera. Será real con PostgreSQL: queda en pendientes |
| Sospecha: `Cita` acepta cualquier estado en el constructor | Real, baja, no corregido | Revisé `Cita.php`: el constructor público no valida `$estado`. `Agenda` solo crea citas Programada, así que hoy no se explota; queda en pendientes |
| Sospecha: dependencias con vulnerabilidades | Sin verificar todavía | Depende del resultado de `composer audit` (sección 1) |

**Lo que la IA no encontró:** al leer el código no vio que `./vendor/bin/phpunit` falla en una carpeta limpia (la prueba `tests/Feature/ExampleTest.php` da `MissingAppKeyException`) porque falta `cp .env.example .env` y `php artisan key:generate`; lo vi al correr las pruebas, y el README ahora lo incluye. Tampoco vio el trait de `User.php:14` ni que Pint y PSR-12 chocan con las clases vacías; los encontraron las herramientas al ejecutarlas.

## 4. Pendientes

| Pendiente | Por qué no se corrigió todavía | Plan |
|---|---|---|
| Control de acceso por rol | Todavía no hay usuarios, login ni rutas restringidas | Cuando se agregue el login: middleware por rol (secretaria, médico) y pruebas de abuso (por ejemplo, un paciente no puede cancelar la cita de otro) |
| Restricción única (médico, inicio) en PostgreSQL | La persistencia llega en otra entrega | Índice único en la base de datos y transacción, para que dos secretarias no reserven la misma hora a la vez |
| `Cita` no valida el estado en el constructor | Habría que decidir con la cliente si se permite crear citas en otros estados | Validar el estado o hacer privado el constructor |
| `laravel/sail` sin uso | Quitarlo cambia `composer.lock` | Quitarlo si no se usa Docker |
| Mayúsculas en el nombre del médico | La cliente no ha definido la regla (ver `registro_pruebas.md`) | Preguntarle a la cliente y agregar la prueba |
| Resultado de `composer audit` | No pude ejecutarlo desde el entorno donde se hizo la revisión (Packagist no respondía) | Correrlo en el computador, pegar el resultado en la sección 1 y corregir lo que salga |

**Claves expuestas:** Ninguna. Revisé todo el historial con `git log -p` y no hay `.env`, claves ni contraseñas subidas; la única contraseña es «password» de la fábrica de usuarios de prueba (H-03).
