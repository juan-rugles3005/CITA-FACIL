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
