# Bitácora de uso de IA

Proyecto: CitaFácil (sistema de gestión de citas de un consultorio médico)
Estudiante: Juan Jose Rugeles Javela
Funcionalidad: HU-06 Registrar una cita en un horario libre sin cruces
Asistente usado: Claude (chat) para el contrato y las pruebas; chat de VS Code en modo Agent para la implementación, el refactor y la revisión de riesgos

## Entradas

### Entrada 1

- **Fecha:** 2026-11-07
- **Prompt o sugerencia:** Subí la tarea de la semana 5 y mis documentos de la semana 3 (requisitos y bitácora) con los mensajes «me ayudas porfa» y «mira estos son los archivos de la semana 3». Claude propuso la HU-06 (Must), el contrato de `registrarCita` con ejemplos y 6 pruebas PHPUnit.
- **Decisión:** Aceptada
- **Por qué:** La HU-06 es Must y resuelve el problema central de la cliente (citas cruzadas, N-02). Las 6 pruebas cubren 2 casos normales, 2 de límite y 2 de error, y el escenario de error usa el mensaje exacto del criterio de aceptación.
- **Verificación:** Leí el contrato y cada prueba. Pegué la sección en el README y copié las pruebas sin cambiarlas. Corrí PHPUnit antes de implementar y las 6 fallaron en rojo (`Class "App\Agenda\Agenda" not found`).
- **Commit relacionado:** a2764d2 «Pruebas de HU-06 registrar cita sin cruces (en rojo)»

### Entrada 2

- **Fecha:** 2026-11-07
- **Prompt o sugerencia:** «Implementa en PHP 8.2 las clases que necesitan las pruebas de tests/Unit/AgendaRegistrarCitaTest.php, siguiendo el contrato de la sección «Funcionalidad de la semana 5» del README.md. Crea en app/Agenda/: Agenda.php, Cita.php (propiedades públicas readonly) y HorarioOcupadoException.php. Usa solo la librería estándar de PHP, sin base de datos. No modifiques ni borres ninguna prueba.»
- **Decisión:** Aceptada
- **Por qué:** El código cumple el contrato: valida bloques de 30 minutos, rechaza el horario ocupado del mismo médico y guarda la cita solo después de pasar las validaciones. Usa solo la librería estándar, como pedía el diseño.
- **Verificación:** Leí `Agenda.php`, `Cita.php` y `HorarioOcupadoException.php`. Con `git status` y `git diff --stat` confirmé que no se modificó ninguna prueba ni el README. Corrí PHPUnit yo mismo: OK (6 tests, 11 assertions).
- **Commit relacionado:** 79bdbe9 «Implementacion de HU-06 registrar cita sin cruces (pruebas en verde)»

### Entrada 3

- **Fecha:** 2026-11-07
- **Prompt o sugerencia:** «Con las 6 pruebas en verde, refactoriza app/Agenda/Agenda.php sin cambiar su comportamiento: reemplaza el valor mágico 30 por una constante BLOQUE_MINUTOS y extrae la validación de la hora a un método privado con un nombre claro. No modifiques ni borres ninguna prueba. Ejecuta las pruebas al terminar.»
- **Decisión:** Aceptada con cambios
- **Por qué:** El asistente creó la constante `BLOQUE_MINUTOS` y el método privado `validarInicioEnBloque`, pero el mensaje de error seguía con el número 30 escrito a mano. Eso dejaba un valor mágico dentro del texto, así que cambié el mensaje para que use la constante.
- **Verificación:** Revisé el diff completo antes de aceptar. Corrí PHPUnit de nuevo: OK (6 tests, 11 assertions), sin tocar las pruebas.
- **Commit relacionado:** d86cbc2 «Refactor: constante BLOQUE_MINUTOS y metodo privado validarInicioEnBloque (sin cambios de comportamiento)» y fdb7070 «Refactor: el mensaje de error usa BLOQUE_MINUTOS en lugar del 30 escrito a mano»

### Entrada 4

- **Fecha:** 2026-11-07
- **Prompt o sugerencia:** «Revisa app/Agenda/Agenda.php y dime qué mejoras o riesgos ves. No cambies nada, solo sugiere.» El asistente señaló un riesgo bajo: `estaOcupado` compara solo la hora de inicio exacta, así que con una hora intermedia como 9:15 devuelve `false` aunque haya una cita de 9:00.
- **Decisión:** Aceptada con cambios
- **Por qué:** El riesgo es real pero no permite duplicar citas, porque `registrarCita` rechaza las horas no alineadas antes de llamar a `estaOcupado`. Cambiar el código iría más allá del contrato, así que ajusté la sugerencia: dejé el código igual y documenté el supuesto en el README.
- **Verificación:** Revisé en `Agenda.php` que la validación de bloque va antes de la consulta de ocupación, y que la prueba de las 9:15 espera `InvalidArgumentException`.
- **Commit relacionado:** 3993c3f «Documenta supuesto de estaOcupado con horas no alineadas»

### Entrada 5

- **Fecha:** 2026-11-07
- **Prompt o sugerencia:** En la misma revisión, el asistente sugirió como mejora futura indexar las citas por médico y hora para no recorrer toda la lista.
- **Decisión:** Rechazada
- **Por qué:** Es una optimización prematura. La cliente atiende unos 30 citas por día (RNF-03), esta versión guarda en memoria según el contrato y la persistencia con PostgreSQL llega en otra entrega. Un índice sumaría complejidad sin necesidad.
- **Verificación:** Comprobé el RNF-03 en mi documento de requisitos de la semana 3 y el supuesto «en memoria» del README. No hubo cambios de código; las 6 pruebas siguen en verde.
- **Commit relacionado:** Ninguno (no se aplicó)

### Entrada 6

- **Fecha:** 2026-11-07
- **Prompt o sugerencia:** El asistente y Claude sugirieron agregar una prueba para comprobar que dos médicos distintos pueden tener cita a la misma hora.
- **Decisión:** Rechazada
- **Por qué:** Decidí no agregarla en esta entrega. Ya tengo 6 pruebas que cubren los casos normales, de límite y de error del criterio de aceptación. Reconozco que ese supuesto del contrato queda sin prueba propia.
- **Verificación:** Revisé la rúbrica (mínimo 5 pruebas con 2 normales, 2 de límite y 1 de error) y mis 6 pruebas la cumplen. El supuesto está documentado en el README.
- **Commit relacionado:** Ninguno (no se aplicó)

## Resumen

| Decisión | Cantidad |
|---|---|
| Aceptadas | 2 |
| Aceptadas con cambios | 2 |
| Rechazadas | 2 |

## Errores que atraparon las pruebas

Las 6 pruebas fallaron en rojo antes de la implementación porque la clase `App\Agenda\Agenda` no existía, y eso confirmó que se ejecutaban y que escribirlas primero tenía sentido. Después, el código del asistente pasó las 6 pruebas a la primera, así que ninguna prueba detectó un error en el código generado. El único riesgo que apareció (`estaOcupado` con horas no alineadas) lo encontró la revisión del asistente, no una prueba.

## Reflexión final (5 a 8 líneas)

El asistente me ayudó más en la implementación: con el contrato y las pruebas como contexto generó las tres clases en menos de un minuto, y las pruebas me dejaron comprobar que cumplían. Leer el código me sirvió para entender por qué se usa `==` y no `===` al comparar fechas, y por qué la cita se guarda solo después de las validaciones. Mis pruebas no atraparon ningún error en el código generado, y eso me enseñó que no cubren todo: el riesgo de `estaOcupado` con las 9:15 salió de la revisión, no de una prueba. Del refactor me quedé con una lección: el asistente dejó el 30 escrito en el mensaje de error y tuve que corregirlo yo. La próxima vez agregaría desde el inicio la prueba de dos médicos a la misma hora, porque mi contrato lo afirma, y pediría la revisión de riesgos antes del refactor y no después.

---

# Semana 7: suite de pruebas con IA

Funcionalidad: pruebas unitarias de `Agenda::registrarCita`, `Agenda::estaOcupado` y `Cita::cambiarEstado` (con técnicas de caja negra)
Asistente usado: Claude (chat) para proponer casos, escribir las pruebas y generar los datos sintéticos

## Entradas de la semana 7

### Entrada 7

- **Fecha:** 2026-11-22
- **Prompt o sugerencia:** Subí el PDF de la tarea de la semana 7, la plantilla `registro_pruebas.md` y el enlace de mi repositorio con «me ayudas hacer esto porfa». Claude leyó el código y propuso las reglas de tres funciones y sus tablas de casos: particiones de equivalencia, valores límite y tablas de decisión (una para `registrarCita` con tres condiciones y otra para las transiciones de estado de `Cita`).
- **Decisión:** Aceptada con cambios
- **Por qué:** Las reglas de `registrarCita` y `estaOcupado` salen del criterio de aceptación y del contrato del README. La de `Cita::cambiarEstado` no estaba escrita: Claude la propuso a partir del alcance de CitaFácil (estados «Atendida» y «Cancelada»), así que la dejé marcada como decisión mía con sus ambigüedades en el registro. Quité del plan las pruebas de 7:30 y 18:00 porque la regla no define horario laboral.
- **Verificación:** Contrasté cada regla con el README y con las ambigüedades del registro (cinco preguntas: particiones, límites, origen del esperado, ambigüedades y reglas inventadas). Cada esperado sale de la regla escrita, no de ejecutar el código.
- **Commit relacionado:** 2f616a9 «Agrega Cita::cambiarEstado y registro de reglas»

### Entrada 8

- **Fecha:** 2026-11-22
- **Prompt o sugerencia:** Claude escribió las pruebas PHPUnit a partir de las reglas y de las tablas: `#[DataProvider]` con un comentario por fila, esperados fijos, tipo exacto de excepción con una función auxiliar (`$e::class`) y sin modificar el código existente. Quedaron 4 archivos nuevos en `tests/Unit/`.
- **Decisión:** Aceptada con cambios
- **Por qué:** En la revisión con las tres preguntas descarté tres pruebas: una que comprobaba el texto de un mensaje que solo existe en el código (el esperado salía del código, no de la regla), una que fijaba la limitación de `estaOcupado` con las 9:15 y un dataset redundante de texto en blanco. El detalle está en la sección 2 del registro.
- **Verificación:** Con `./vendor/bin/phpunit` pasan las 55 pruebas (126 aserciones) sin tocar el código de la semana 5. Ningún esperado se ajustó para que pasara.
- **Commit relacionado:** e326716 «Pruebas de la semana 7»

### Entrada 9

- **Fecha:** 2026-11-22
- **Prompt o sugerencia:** Claude generó datos sintéticos con contexto local en `tests/Fixtures/datos_sinteticos.json`: seis médicos y seis pacientes ficticios con tildes, ñ, apóstrofo (O'Neill, D'Ávila), guion, espacio al final, nombre vacío, cédulas `1000000001` a `1000000006` y teléfonos `3000000001` a `3000000006`.
- **Decisión:** Aceptada con cambios
- **Por qué:** Los datos no corresponden a personas reales. Quité de la lista de textos vacíos el valor solo de espacios porque quedaba en la misma partición que el texto vacío. No usé valores de cédula o médico vacíos para registrar citas, porque la regla no define qué pasa con ellos.
- **Verificación:** Revisé el archivo a mano y lo usé en `AgendaDatosSinteticosTest` con un `DataProvider` por médico. Si cambio un médico en el JSON, la prueba lo toma sin cambiar código.
- **Commit relacionado:** 069898d «Datos sinteticos de pruebas»

### Entrada 10

- **Fecha:** 2026-11-22
- **Prompt o sugerencia:** Pedí romper el código a propósito. Claude propuso errores uno a uno (cambiar el 30 por 15, quitar la comparación del médico, `==` por `===`, devolver siempre `false`, permitir Programada → Programada, entre otros) y revisó qué pruebas fallaban.
- **Decisión:** Aceptada
- **Por qué:** En la primera tanda ninguna prueba de los límites de minuto ni de los estados finales falló, y eso podía significar que eran débiles. Se agregaron errores pensados para ellas y sí fallaron. Dos errores sobrevivieron: uno es equivalente (el orden de las validaciones) y el otro depende de una ambigüedad (mayúsculas en el nombre del médico), así que no agregué casos.
- **Verificación:** La tabla de la sección 3 del registro tiene cada cambio, el archivo y la línea, y las pruebas que fallaron. Cada cambio se deshizo antes del siguiente y la suite volvió a OK.
- **Commit relacionado:** Ninguno (los cambios se deshicieron; no quedan en el repositorio)

## Resumen de la semana 7

| Decisión | Cantidad |
|---|---|
| Aceptadas | 1 |
| Aceptadas con cambios | 3 |
| Rechazadas | 0 |

Pruebas descartadas en la revisión: 3 (mensaje de la excepción, hora no alineada en `estaOcupado`, texto en blanco repetido).

## Reflexión de la semana 7

La IA me ahorró escribir las tablas y las pruebas, pero la revisión fue lo que importó: una de las pruebas tenía el texto de un mensaje que solo estaba en el código y habría pasado aunque la regla fuera otra. Con los errores a propósito vi que algunas pruebas no fallaban con los primeros cambios y que mi lista de errores era corta, no que las pruebas sobraran. La prueba que me quedó pendiente es la de mayúsculas en el nombre del médico, porque primero hay que preguntarle a la cliente cuál es la regla.

---

# Semana 6: revisión de código y seguridad

Funcionalidad: revisión del incremento (linter, lista de seguridad, segunda revisión con IA), correcciones, README y docstrings
Asistente usado: Claude (chat) para la revisión y para hacer las correcciones en una rama propia (`semana-6-revision`)

## Entradas de la semana 6

### Entrada 11

- **Fecha:** 2026-11-25
- **Prompt o sugerencia:** «Revisa app/Agenda, routes, database, config y .env.example con criterios de corrección, diseño y seguridad (inyección, secretos, contraseñas, control de acceso, dependencias). Dame una tabla de hallazgos con archivo, línea y severidad, y separa los problemas seguros de las sospechas.» Claude devolvió 4 problemas seguros (validación de entradas, `APP_DEBUG=true`, usuario de prueba con contraseña «password», línea larga con valor mágico) y 4 sospechas (`==` con fechas, carrera al guardar, estado libre en el constructor de `Cita`, dependencias).
- **Decisión:** Aceptada con cambios
- **Por qué:** Los 4 seguros eran reales. De las sospechas, dos eran falsos positivos (`==` compara el instante, y no hay carrera mientras las citas estén en memoria), una es real pero baja y queda pendiente (el estado en el constructor), y la de dependencias depende de `composer audit`.
- **Verificación:** Comprobé cada hallazgo en el código del commit 42a6a83: probé `registrarCita` con texto de SQL y HTML (la aceptaba), leí las líneas indicadas y probé que `==` entre fechas de zonas horarias distintas compara el instante. Lo que la IA no vio (la prueba Feature falla sin `APP_KEY`, el trait de `User.php`) apareció al ejecutar las pruebas y el linter. La clasificación completa está en la sección 3 de `revision_seguridad.md`.
- **Commit relacionado:** 62cbc1f «Informe de revision de codigo y seguridad (revision_seguridad.md)»

### Entrada 12

- **Fecha:** 2026-11-25
- **Prompt o sugerencia:** Corregir H-01: validar la cédula (6 a 10 dígitos) y el médico (no vacío, máximo 100 caracteres, sin caracteres de control) en `registrarCita`, y escribir pruebas de abuso con inyección SQL, HTML, saltos de línea, vacío y texto largo, sin tocar las pruebas existentes.
- **Decisión:** Aceptada
- **Por qué:** La agenda aceptaba cualquier texto y, al conectar PostgreSQL y las vistas, eso abriría la puerta a inyección y a registros falsos. Las reglas elegidas no cambian los casos de las semanas 5 y 7: todas las cédulas y médicos de esas pruebas siguen siendo válidos (el médico con espacio al final y el de apóstrofo también).
- **Verificación:** Corrí las 15 pruebas nuevas contra el código anterior: 14 fallan (la de 100 caracteres exactos pasa porque ya aceptaba ese caso), y con la corrección pasan todas. Las 56 pruebas anteriores pasan sin modificarlas (71 en total).
- **Commit relacionado:** 3f1601d «Seguridad: valida cedula (6 a 10 digitos) y medico (no vacio, maximo 100, sin caracteres de control) en registrarCita»

### Entrada 13

- **Fecha:** 2026-11-25
- **Prompt o sugerencia:** Instrucción delimitada al asistente trabajando como agente de código en la rama `semana-6-revision`: configurar PHP_CodeSniffer, PHPStan y Pint; corregir los avisos y los hallazgos H-01 a H-03; poner docstrings y reescribir el README; un commit por corrección, sin modificar ninguna prueba existente y sin tocar nada fuera del alcance.
- **Decisión:** Aceptada con cambios
- **Por qué:** El agente dejó Pint y PSR-12 en conflicto con la clase vacía `HorarioOcupadoException` (Pint la compactaba a una línea y PHPCS la rechazaba). Se resolvió desactivando la regla `single_line_empty_body` en `pint.json` y dejando la clase con sus llaves en líneas separadas. También se corrigió un commit que había quedado con un aviso de Pint pendiente.
- **Verificación:** Antes de integrar la rama reviso el diff completo con `git diff main..semana-6-revision` y confirmo que ningún archivo de `tests/` existente cambió (solo se agregó `AgendaEntradasMaliciosasTest.php`). PHPCS, PHPStan nivel 8 y Pint salen sin avisos y las 71 pruebas pasan.
- **Commit relacionado:** 577035a, bfaec93, 7cb696b, 3f1601d, b8b9c21, 3a88e40, d00b4ee (rama `semana-6-revision`)

## Resumen de la semana 6

| Decisión | Cantidad |
|---|---|
| Aceptadas | 1 |
| Aceptadas con cambios | 2 |
| Rechazadas | 0 |

---

# Semanas 1 a 4 (documentos en PDF)

Las bitácoras de prompts de esas semanas están dentro de sus documentos, en la carpeta [`docs/`](docs/README.md):

| Semana | Dónde está el uso de IA |
|---|---|
| 1 | Sección «Uso de la IA en esta tarea» de [S1_Mapa_IA_ciclo_de_vida.pdf](docs/modulo-1-fundamentos/S1_Mapa_IA_ciclo_de_vida.pdf) |
| 2 | Línea «Uso de IA» de [S2_Propuesta.pdf](docs/modulo-2-propuesta/S2_Propuesta.pdf) |
| 3 | [S3_Bitacora_prompts.pdf](docs/modulo-3-requisitos/S3_Bitacora_prompts.pdf) (6 prompts) y sección 7 de [S3_Requisitos.pdf](docs/modulo-3-requisitos/S3_Requisitos.pdf) |
| 4 | Sección 7 de [S4_Diseno_y_ADR-001.pdf](docs/modulo-4-diseno/S4_Diseno_y_ADR-001.pdf) |

---

# Semanas 9 y 10: pipeline, contenedor y entrega final

Funcionalidad: pipeline de integración continua, Dockerfile y documentos de operación de CitaFácil
Asistente usado: Claude (chat) para escribir el pipeline, el Dockerfile y los documentos de operación

## Entradas de las semanas 9 y 10

### Entrada 14

- **Fecha:** 2026-11-26
- **Prompt o sugerencia:** Le dije a Claude que no tenía el pipeline de la semana 9. Claude escribió `.github/workflows/ci.yml` (push y pull request a `main`, permisos de solo lectura, linter, `composer audit`, pruebas con cobertura y construcción de la imagen Docker), un `Dockerfile` en dos etapas con usuario sin privilegios, un `.dockerignore` y `dependabot.yml`.
- **Decisión:** Aceptada con cambios
- **Por qué:** La estructura cumple la lista de la semana (eventos, permisos, secretos, versiones, dependencias y que un fallo haga fallar el pipeline). Hadolint encontró 2 avisos en el Dockerfile (usuario no numérico y `HEALTHCHECK` sin formato JSON) y se corrigieron antes del commit. Además, `php artisan serve` es un servidor de desarrollo; lo dejé documentado como límite en `docs/plan_operacion.md` en vez de fingir que es de producción.
- **Verificación:** actionlint sin avisos sobre `ci.yml` y hadolint sin avisos sobre el Dockerfile; en mi equipo pasan las 71 pruebas y `/up` responde 200. En GitHub la primera ejecución salió en **rojo** ([CI #1](https://github.com/juan-rugles3005/CITA-FACIL/actions/runs/45909817751)): el paso `composer audit` encontró 5 avisos de seguridad en 3 paquetes de Symfony (http-kernel, mailer y routing), que mi auditoría local del día anterior no había mostrado, así que el pipeline impidió seguir con las pruebas. Actualicé `symfony/*` con `composer update` (commit 78f898c), `composer audit` quedó limpio y la ejecución siguiente salió en **verde** ([CI #9](https://github.com/juan-rugles3005/CITA-FACIL/actions/runs/45912132087)): 71 pruebas, 142 aserciones, y la imagen Docker se construye, responde en `/up` y no corre como root. Cobertura medida por el pipeline: `Agenda` y `Cita` (la lógica del negocio) 100 % de métodos y de líneas (35 de 35); todo `app/` 90,24 % de líneas (37 de 41).
- **Commit relacionado:** 7098383 «Pipeline de CI (GitHub Actions) y Dockerfile» y 78f898c «Actualiza symfony/*… por 5 avisos de seguridad detectados por composer audit en el pipeline»

### Entrada 15

- **Fecha:** 2026-11-26
- **Prompt o sugerencia:** Revisar con la lista de la semana 9 los archivos `ci.yml` y `Dockerfile` generados por IA del código de práctica (Tienda La Esquina), y escribir `docs/revision_configuracion.md`, la guía de despliegue y el plan de operación.
- **Decisión:** Aceptada con cambios
- **Por qué:** La revisión encontró 12 problemas (6 en el pipeline y 6 en el Dockerfile, contando el `.dockerignore` que faltaba) y una línea que parece un problema y no lo es (`--host 0.0.0.0`). Cambié el texto del documento para dejar claro que esos archivos son de práctica y no de CitaFácil. En el plan de operación quité cualquier monitoreo de modelos, porque CitaFácil no tiene ningún modelo de IA desplegado.
- **Verificación:** Corrí actionlint (0 avisos: no vio ninguno de los 6 problemas del pipeline) y hadolint (2 avisos: solo vio `latest` y el `pip` sin `--no-cache-dir`). Los demás los encontré leyendo los archivos con la lista de la semana.
- **Commit relacionado:** 6e2f5a7 «Documentos del modulo 6: revision de configuracion, guia de despliegue y plan de operacion»

## Resumen de las semanas 9 y 10

| Decisión | Cantidad |
|---|---|
| Aceptadas | 0 |
| Aceptadas con cambios | 2 |
| Rechazadas | 0 |

---

# Semana 10: pantalla de demostración

Funcionalidad: pantalla web mínima para ver funcionando la agenda (registrar cita, horario ocupado, cambiar estado)
Asistente usado: Claude (chat), escribió el controlador, la vista y las pruebas en la rama `semana-10-pantalla`

## Entradas de la semana 10 (pantalla)

### Entrada 16

- **Fecha:** 2026-11-27
- **Prompt o sugerencia:** Le dije a Claude que el profesor quiere ver el programa funcionando. Claude propuso una pantalla mínima que usa `Agenda` y `Cita` sin cambiarlas: `AgendaController`, la vista `agenda.blade.php`, cuatro rutas y 9 pruebas de la pantalla. Las citas se guardan en la sesión porque todavía no hay base de datos.
- **Decisión:** Aceptada con cambios
- **Por qué:** Es lo mínimo para mostrar la lógica ya probada sin inventar funcionalidades que no existen (no hay login ni PostgreSQL, y la pantalla lo dice). Durante la revisión el linter rechazó el primer código: PHPCS y Pint chocaban en la concatenación con `.`, en el `catch` múltiple y en `new Agenda`, y se resolvió con `sprintf`, dos bloques `catch` y una regla en `pint.json`. Una prueba mía también estaba mal: comprobaba que no apareciera la cédula rechazada, pero el formulario la vuelve a mostrar.
- **Verificación:** Pasan las 80 pruebas (las 71 anteriores sin modificar y 9 nuevas), PHPCS, PHPStan nivel 8 y Pint sin avisos. Probé el flujo con `php artisan serve`: registrar una cita, intentar otra a la misma hora (sale «Ese horario ya está ocupado») y `/up` responde 200. Lo que debo revisar yo antes de mostrarlo: que se vea bien en mi navegador.
- **Commit relacionado:** rama `semana-10-pantalla` (pendiente de integrar a `main`)
