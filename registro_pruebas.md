# Registro de pruebas: tabla de casos y pruebas de la IA

Proyecto: CitaFácil (sistema de gestión de citas de un consultorio médico)
Estudiante: Juan Jose Rugeles Javela
Fecha: 2026-11-22

Pruebas en `tests/Unit/` (PHPUnit; `#[DataProvider]` equivale a `parametrize` de pytest y `lanza()` comprueba el tipo exacto de excepción como `pytest.raises`). Para correrlas: `./vendor/bin/phpunit`.

## 1. Tabla de casos

### Regla R1: `Agenda::registrarCita` (varias condiciones)

**Regla completa (el oráculo):** HU-06 y contrato del README. La cita se registra solo si (a) la hora de inicio está alineada a bloques de 30 minutos (minutos 00 o 30, sin segundos ni fracciones) y (b) el médico no tiene ya una cita con ese mismo inicio (fecha y hora completas). La ocupación es por médico: dos médicos distintos pueden tener cita a la misma hora. Si la hora no está alineada lanza `InvalidArgumentException` (se valida antes que la ocupación); si el horario está ocupado lanza `HorarioOcupadoException` con el mensaje «Ese horario ya está ocupado» y no crea la cita; si no, devuelve una `Cita` «Programada» y la guarda.

| Técnica | Partición o límite | Entrada | Esperado | Prueba que lo cubre |
|---|---|---|---|---|
| Partición válida | Minuto 00 | 2026-10-28 09:00:00 | Cita «Programada», 1 cita guardada | test_acepta_horas_alineadas_a_bloques_de_30_minutos[P1 en punto 09:00] |
| Partición válida | Minuto 30 | 2026-10-28 09:30:00 | Cita «Programada» | test_acepta_horas_alineadas_a_bloques_de_30_minutos[P2 y media 09:30] |
| Límite | Primer bloque del día | 08:00:00 | Cita «Programada» | test_acepta_horas_alineadas_a_bloques_de_30_minutos[L1 primer bloque 08:00] |
| Límite | Último bloque del día | 17:30:00 | Cita «Programada» | test_acepta_horas_alineadas_a_bloques_de_30_minutos[L2 último bloque 17:30] |
| Partición inválida | Minuto intermedio | 09:15:00 | InvalidArgumentException, 0 citas | test_rechaza_horas_no_alineadas_y_no_crea_la_cita[P3 minuto intermedio 09:15] |
| Partición inválida | Segundos distintos de cero | 09:00:30 | InvalidArgumentException | test_rechaza_horas_no_alineadas_y_no_crea_la_cita[P4 segundos 09:00:30] |
| Límite | Último valor antes del bloque :30 | 09:29:00 | InvalidArgumentException | ...[L3 minuto 29 (09:29)] |
| Límite | Primer valor después del bloque :30 | 09:31:00 | InvalidArgumentException | ...[L4 minuto 31 (09:31)] |
| Límite | Primer valor después del bloque :00 | 09:01:00 | InvalidArgumentException | ...[L5 minuto 01 (09:01)] |
| Límite | Último valor antes del siguiente :00 | 09:59:00 | InvalidArgumentException | ...[L6 minuto 59 (09:59)] |
| Límite | Un segundo después del bloque | 09:00:01 | InvalidArgumentException | ...[L7 un segundo (09:00:01)] |
| Límite | Una fracción de segundo después del bloque | 09:00:00.000001 | InvalidArgumentException | ...[L8 un microsegundo (09:00:00.000001)] |
| Partición inválida | Horario ocupado (mismo médico, misma hora) | cita 09:00 Dra. Torres y se repite | HorarioOcupadoException «Ese horario ya está ocupado», 1 cita | test_tabla_de_decision_de_registrar_cita[D2 mismo médico, misma hora] |
| Límite | Bloque contiguo al ocupado | cita 09:00 y se pide 09:30 | Cita «Programada», 2 citas | test_tabla_de_decision_de_registrar_cita[D3 mismo médico, bloque contiguo] |

**Ambigüedades encontradas:**
1. El README dice que 8:00 y 17:30 son el primer y el último bloque, pero también que no se valida el horario laboral. No hay «primer valor fuera» (7:30, 18:00) en la regla; por eso L1 y L2 son casos válidos y no se prueban 7:30 ni 18:00. Pendiente de definir con la cliente.
2. Los nombres de médico se comparan exactos. La regla no dice si «dra. torres» y «Dra. Torres» son el mismo médico; no se prueba hasta que la cliente lo defina (ver mutante M25 en la sección 3).
3. La regla no dice qué pasa con cédula o médico vacíos (hoy se aceptan). No se prueba ese comportamiento para no fijar algo que no es una regla.
4. El README solo fija el tipo de excepción para la hora no alineada, no su texto; por eso no se prueba el mensaje (ver sección 2).

**Tabla de decisión de R1** (condiciones: A = hora alineada, B = el médico es el mismo de una cita existente, C = misma fecha y hora que esa cita; 8 combinaciones simplificadas a 5 reglas, porque si A = No las otras no importan):

| Fila | A alineada | B mismo médico | C misma fecha y hora | Resultado | Prueba |
|---|---|---|---|---|---|
| D1 | No | no importa | no importa | InvalidArgumentException, no se crea | [D1 no alineada] |
| D2 | Sí | Sí | Sí | HorarioOcupadoException | [D2 mismo médico, misma hora] |
| D3 | Sí | Sí | No (bloque contiguo) | Se registra | [D3 mismo médico, bloque contiguo] |
| D4 | Sí | Sí | No (mismo reloj, otro día) | Se registra | [D4 mismo médico, mismo reloj otro día] |
| D5 | Sí | No | Sí | Se registra | [D5 otro médico, misma hora] |
| D6 | Sí | No | No | Se registra | [D6 otro médico, otra hora] |
| D7 | Sí | (agenda vacía) | (agenda vacía) | Se registra | [D7 agenda vacía] |

Todas son casos de `test_tabla_de_decision_de_registrar_cita`. D3 y D4 son las dos formas de «C = No»; D7 es el caso de la agenda vacía.

### Regla R2: `Agenda::estaOcupado`

**Regla completa (el oráculo):** contrato del README. Devuelve `true` si el médico indicado tiene una cita con ese mismo inicio (fecha y hora exactas); en cualquier otro caso devuelve `false`. Dos médicos distintos no se bloquean entre sí.

| Técnica | Partición o límite | Entrada | Esperado | Prueba que lo cubre |
|---|---|---|---|---|
| Partición | Mismo médico y mismo inicio | cita 09:00 Dra. Torres, consulta lo mismo | true | test_estaOcupado_segun_la_regla[P1 mismo médico y mismo inicio] |
| Partición | Otro médico, mismo inicio | consulta Dr. Cabrera 09:00 | false | [P2 otro médico] |
| Partición | Mismo médico, mismo reloj en otro día | consulta 2026-10-29 09:00 | false | [P3 otro día] |
| Límite | Bloque anterior al ocupado | consulta 08:30 | false | [L1 bloque anterior 08:30] |
| Límite | Bloque siguiente al ocupado | consulta 09:30 | false | [L2 bloque siguiente 09:30] |
| Partición | Agenda vacía | consulta 09:00 sin citas | false | test_agenda_vacia_no_tiene_horarios_ocupados |
| Partición | Varias citas en la agenda | citas 09:00 y 10:00; consulta 09:00, 10:00 y 09:30 | true, true, false | test_con_varias_citas_cada_horario_ocupado_se_detecta_y_los_libres_no |
| Partición inválida | Texto vacío como médico | médico "" con agenda vacía | false | test_consultar_un_medico_vacio_en_una_agenda_vacia_no_esta_ocupado[texto vacío ""] |

**Ambigüedades encontradas:** el README documenta que `estaOcupado` devuelve `false` con una hora no alineada (9:15) aunque haya una cita a las 9:00. Eso es una limitación del código, no una regla de la cliente (una cita de 9:00 a 9:30 haría pensar que 9:15 está ocupado). No se prueba (sección 2).

### Regla R3: `Cita::cambiarEstado` (función nueva de esta semana)

**Regla completa (el oráculo):** alcance de CitaFácil (estados «Programada», «Atendida» y «Cancelada»). Una cita nace «Programada». Desde «Programada» puede pasar a «Atendida» o a «Cancelada». «Atendida» y «Cancelada» son finales: no admiten ningún cambio. Pasar de «Programada» a «Programada» no es un cambio válido. Un estado que no sea uno de los tres (incluido el texto vacío) lanza `InvalidArgumentException` y se revisa antes que la transición. Una transición no permitida lanza `DomainException`. La cita original no cambia: la función devuelve una cita nueva con los mismos datos y el estado nuevo.

| Técnica | Partición o límite | Entrada | Esperado | Prueba que lo cubre |
|---|---|---|---|---|
| Partición | Cita nueva | `new Cita(...)` sin estado | «Programada» | test_una_cita_nueva_nace_programada |
| Partición | Transición válida | Programada → Atendida | «Atendida» | test_tabla_de_transiciones_de_estado[T1 Programada a Atendida] |
| Partición | Transición válida | Programada → Cancelada | «Cancelada» | [T2 Programada a Cancelada] |
| Límite | Mismo estado | Programada → Programada | DomainException | [T3 Programada a Programada] |
| Partición inválida | Estado final | Atendida → Programada / Atendida / Cancelada | DomainException | [T4], [T5], [T6] |
| Partición inválida | Estado final | Cancelada → Programada / Atendida / Cancelada | DomainException | [T7], [T8], [T9] |
| Partición inválida | Estado inexistente | Programada → «Reprogramada» | InvalidArgumentException | [T10 Programada a Reprogramada] |
| Partición inválida | Estado inexistente desde estado final | Atendida → «Reprogramada» | InvalidArgumentException (gana la validación del estado) | [T11 Atendida a Reprogramada] |
| Partición inválida | Texto vacío | Programada → "" | InvalidArgumentException | [T12 Programada a vacío] |
| Partición | Inmutabilidad | Programada → Atendida | cita nueva con mismos datos; la original sigue «Programada» | test_el_cambio_devuelve_una_cita_nueva_con_los_mismos_datos_y_no_modifica_la_original |

**Ambigüedades encontradas:** esta regla no estaba escrita en el README ni en el contrato de la semana 5; salió del alcance de CitaFácil (estados «Atendida» y «Cancelada»). Las decisiones que tomé: (1) Programada → Programada es error y no un cambio vacío; (2) un estado inexistente se valida antes que la transición; (3) los estados distinguen mayúsculas («atendida» sería inexistente, no se prueba); (4) falta definir si cancelar una cita libera el horario en `Agenda`; esta semana no se implementa ni se prueba.

**Tabla de decisión de R3:** son las filas T1 a T12 de arriba, con estado actual × estado nuevo (3 × 3 combinaciones más el estado inexistente).

## 2. Pruebas de la IA descartadas o corregidas

**Prompt usado:** pegué el PDF de la tarea, la plantilla del registro y el enlace del repositorio (`me ayudas hacer esto porfa`) y Claude propuso las reglas, las tablas y la primera versión de las pruebas. Detalle en las entradas 7 y 8 de `bitacora_ia.md`.

| Prueba generada | Decisión | Por qué | En su lugar |
|---|---|---|---|
| test_el_mensaje_de_hora_no_alineada_menciona_los_30_minutos: `assertSame('La hora debe estar alineada a bloques de 30 minutos', $e->getMessage())` | Descartada | El esperado salió del código (`Agenda.php`, línea 35), no de la regla: el contrato solo define el tipo de excepción. Fallaría si se mejora la redacción del mensaje, sin que haya un defecto. | El tipo exacto (`$e::class`) se comprueba en `test_rechaza_horas_no_alineadas_y_no_crea_la_cita` |
| estaOcupado con hora no alineada (fila P4: cita 09:00, consulta 09:15, esperado `false`) | Descartada | El esperado viene de una limitación documentada del código, no de la regla de la cliente. Si se corrige `estaOcupado` para detectar el solapamiento, esta prueba fallaría por una mejora. | Anotada como ambigüedad en R2; se decide con la cliente |
| Dataset «texto vacío "   "» del médico (solo espacios) | Descartada | Redundante: está en la misma partición que `""` (agenda vacía y médico sin texto útil → `false`) y no cubre nada distinto. | Se quitó del fixture; queda `""` |
| Filas L3 a L6 (minutos 29, 31, 01, 59) y T4 a T7 (estados finales) | Conservada | En la primera tanda de 16 errores a propósito ninguna de estas pruebas falló, así que parecían débiles. Probé errores pensados para ellas (M17, M18, M19, M24) y sí fallan: no son redundantes. | Sin cambios |
| Todas las demás (P1, P2, L1, L2, D1 a D7, P1 a P5 de R2, T1 a T3, T8 a T12, inmutabilidad, cita nueva, médicos sintéticos) | Conservada | Cada una tiene su esperado en la regla escrita, falla con al menos un error a propósito (sección 3) y cubre una partición, un límite o una fila de decisión que ninguna otra cubre. | Sin cambios |

**Decisión:** 3 descartadas, 0 corregidas, el resto conservadas. Ningún esperado se ajustó para que la prueba pasara; todas pasaron a la primera contra el código sin tocarlo.

## 3. Romper el código a propósito

Un cambio a la vez, con la suite completa (55 pruebas) y deshaciendo cada cambio antes del siguiente.

| Cambio introducido | Archivo y línea | ¿Alguna prueba falló? | Qué hice |
|---|---|---|---|
| M1: `BLOQUE_MINUTOS = 30` por `15` (cambia el porcentaje) | Agenda.php:10 | Sí, 3: P3 minuto 09:15, D1 no alineada y la prueba de 9:15 de la semana 5 | Nada: la suite protege el tamaño del bloque |
| M2: quitar `\|\| $inicio->format('s.u') !== '00.000000'` | Agenda.php:34 | Sí, 3: P4 segundos, L7 un segundo, L8 un microsegundo | Nada |
| M3: `estaOcupado` sin comparar el médico | Agenda.php:42 | Sí, 3: P2 otro médico, D5 otro médico misma hora y todos los médicos sintéticos | Nada |
| M4: `estaOcupado` devuelve siempre `false` (`return true` por `return false`) | Agenda.php:43 | Sí, 13 | Nada |
| M5: `==` por `===` al comparar las fechas | Agenda.php:42 | Sí, 6 | Nada |
| M9: guardar la cita antes de revisar si el horario está ocupado | Agenda.php:22 | Sí, 29 | Nada |
| M12: comparar solo `H:i` y no la fecha | Agenda.php:42 | Sí, 2: P3 otro día y D4 mismo reloj otro día | Nada |
| M14: `BLOQUE_MINUTOS = 60` (el minuto 30 deja de ser válido) | Agenda.php:10 | Sí, 5 | Nada |
| M15 y M16: `==` por `>=` y por `<=` en `estaOcupado` | Agenda.php:42 | Sí, 2 y 8 | Nada: L1 (08:30) y L2 (09:30) atrapan los dos |
| M17: aceptar los minutos 01 y 31 (`% 30 > 1`) | Agenda.php:34 | Sí, 2: L4 y L5 | Nada |
| M18: aceptar los minutos 29 y 59 (desfase de uno) | Agenda.php:34 | Sí, 2: L3 y L6 | Nada |
| M22: `estaOcupado` solo mira la primera cita de la lista | Agenda.php:41 | Sí, 3, entre ellas «varias citas» | Nada |
| M6: permitir Programada → Programada (quitar `\|\| $nuevoEstado === self::PROGRAMADA`) | Cita.php:38 | Sí, 1: T3 | Nada |
| M7: Cancelada deja de ser final | Cita.php:38 | Sí, 2: T8 y T9 | Nada |
| M24: Atendida deja de ser final | Cita.php:38 | Sí, 2: T5 y T6 | Nada |
| M19: permitir «reabrir» una cita final como Programada | Cita.php:38 | Sí, 2: T4 y T7 | Nada |
| M8 y M20: `cambiarEstado` devuelve siempre «Atendida» / siempre «Cancelada» | Cita.php:42 | Sí, 1 y 1: T2 y T1 | Nada |
| M13: la cita nueva pierde la cédula | Cita.php:42 | Sí, 1: la prueba de inmutabilidad | Nada |
| M11: no validar el estado desconocido | Cita.php:34 | Sí, 3: T10, T11, T12 | Nada |
| M10: el estado por defecto pasa a «Cancelada» | Cita.php:21 | Sí, 12 | Nada |
| M25: comparar el médico sin distinguir mayúsculas (`strcasecmp`) | Agenda.php:42 | **No** | No agregué un caso: la regla no dice si «dra. torres» es el mismo médico (ambigüedad 2 de R1). Queda pendiente de la cliente. |
| M26: revisar la ocupación antes de la alineación | Agenda.php:20-24 | **No** | Es un mutante equivalente: una hora no alineada nunca puede estar ocupada, así que el resultado observable es el mismo. No hay caso por agregar. |

Cada cambio se deshizo antes de probar el siguiente y la suite volvió a OK (55 pruebas, 126 aserciones).

## 4. Datos de prueba

**Origen:** sintéticos generados con IA (Claude) el 2026-11-22, guardados en `tests/Fixtures/datos_sinteticos.json` y cargados por los `DataProvider` de `AgendaDatosSinteticosTest`. Confirmo que no hay datos de personas reales: los nombres y los médicos son inventados, las cédulas son `1000000001` a `1000000006` y los teléfonos `3000000001` a `3000000006`, que no corresponden a nadie.

**Casos difíciles incluidos:** tildes (Sofía, Andrés, Lucía, Iván, María Ángeles, José, Cuéllar, Ríos), ñ (Muñoz, Peña, Quiñones, Ñáñez, Zúñiga), apóstrofo (O'Neill, D'Ávila), guion en un apellido (Pérez-Gómez), espacio al final del nombre («Dr. Zúñiga »), nombre y teléfono vacíos (último paciente), texto vacío como médico consultado, y seis médicos distintos atendiendo a la misma hora. Para el contexto local se usaron apellidos y nombres comunes de la región (Neiva, Huila). Hoy solo se usan las cédulas y los médicos en las pruebas; el nombre y el teléfono quedan listos para el módulo de pacientes.
