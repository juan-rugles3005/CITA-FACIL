# CitaFácil

## 1. Qué es

CitaFácil es el sistema de gestión de citas de un consultorio médico. Evita que la secretaria asigne la misma hora a dos pacientes con el mismo médico. En esta versión está hecha la lógica de la agenda (HU-06 registrar una cita sin cruces y el cambio de estado de una cita); las citas se guardan en memoria y la persistencia en PostgreSQL llega en una entrega posterior. El proyecto está hecho con Laravel 12.

## 2. Requisitos

- PHP 8.2 o superior con las extensiones `mbstring`, `xml`, `curl` y `sqlite3` (las pruebas de Laravel usan SQLite en memoria)
- Composer 2
- Git
- Node.js 20 o superior y npm: solo si quieres compilar los estilos con Vite (no hacen falta para las pruebas)

## 3. Instalación

```bash
git clone https://github.com/juan-rugles3005/CITA-FACIL.git
cd CITA-FACIL
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

En Windows (PowerShell) usa `New-Item database/database.sqlite` en lugar de `touch`. El archivo `.env` es tuyo: no se sube a Git (está en `.gitignore`). Si algún día pones claves o contraseñas de base de datos, van solo ahí y nunca en `.env.example`.

## 4. Ejecución

```bash
php artisan serve
```

Abre http://127.0.0.1:8000 para ver la página de inicio. La agenda todavía no tiene pantallas; se puede probar desde la consola:

```bash
php artisan tinker
```

```php
$agenda = new App\Agenda\Agenda();
$agenda->registrarCita('1075234567', 'Dra. Torres', new DateTimeImmutable('2026-10-28 09:00'));
$agenda->estaOcupado('Dra. Torres', new DateTimeImmutable('2026-10-28 09:00')); // true
```

## 5. Pruebas

```bash
./vendor/bin/phpunit
```

Revisión de calidad y seguridad (PHP_CodeSniffer con PSR-12 y reglas de seguridad, PHPStan nivel 8 y Pint):

```bash
composer require --dev phpstan/phpstan:2.1.17 squizlabs/php_codesniffer:3.13.2   # solo la primera vez
composer lint
composer audit
```

El detalle de las reglas y los casos de prueba está en [`registro_pruebas.md`](registro_pruebas.md), y el informe de la revisión de código y seguridad en [`revision_seguridad.md`](revision_seguridad.md).

## 6. Estructura

```
app/Agenda/          Lógica de la agenda: Agenda, Cita y HorarioOcupadoException
app/Models/User.php  Modelo de usuario que trae Laravel (todavía sin usar)
tests/Unit/          Pruebas de la agenda, de los estados de la cita y de entradas maliciosas
tests/Fixtures/      Datos sintéticos de pacientes y médicos (no son personas reales)
phpcs.xml            Reglas de PHP_CodeSniffer (estilo y seguridad)
phpstan.neon         Configuración de PHPStan
pint.json            Configuración de Pint
registro_pruebas.md  Tabla de casos de la semana 7
revision_seguridad.md  Informe de la revisión de la semana 6
bitacora_ia.md       Bitácora de uso de la IA
```

## 7. Uso de IA

La IA (Claude, y el chat de VS Code en modo Agent) ayudó a escribir el contrato y las pruebas de la HU-06, la implementación y el refactor, los casos y los datos sintéticos de las pruebas, y la segunda revisión de seguridad de la semana 6. Cada sugerencia se verificó en el código antes de aceptarla, y las que no servían se rechazaron o se cambiaron. Las entradas con la decisión, el motivo y el commit están en [`bitacora_ia.md`](bitacora_ia.md).

## 8. Autor y licencia

Autor: Juan José Rugeles Javela, estudiante de Ingeniería de Software. Proyecto del seminario de grado.

Licencia: MIT, la del esqueleto de Laravel sobre el que se construyó (ver `composer.json`).

---

# Anexos de entregas anteriores

## Funcionalidad de la semana 5

### Historia de usuario

**HU-06 (Must).** Como secretaria del consultorio, quiero registrar una cita en un horario libre para que no se asigne la misma hora a dos pacientes. *(Necesidad de origen: N-02)*

**Criterio de aceptación**

- **Escenario 1: cita registrada.** Dado que el horario de las 9:00 del 28/10/2026 está libre, cuando registro la cita de «Carlos Mejía» con la Dra. Torres, entonces la cita queda guardada y esa hora pasa a ocupada.
- **Escenario 2 (error): horario ocupado.** Dado que las 9:00 del 28/10/2026 ya tienen la cita de «Carlos Mejía», cuando intento registrar a «Laura Gómez» a esa hora, entonces el sistema muestra «Ese horario ya está ocupado» y no crea la cita.

### Contrato de la función

```php
App\Agenda\Agenda::registrarCita(
    string $cedulaPaciente,
    string $medico,
    DateTimeImmutable $inicio
): Cita
```

| Elemento | Descripción |
|---|---|
| Recibe | Cédula del paciente, nombre del médico y fecha y hora de inicio de la cita. |
| Devuelve | Un objeto `Cita` con `cedulaPaciente`, `medico`, `inicio` y `estado = "Programada"`. La cita queda guardada en la agenda. |
| Lanza | `HorarioOcupadoException` con el mensaje «Ese horario ya está ocupado» si el médico ya tiene una cita a esa hora. `InvalidArgumentException` si la hora no está alineada a bloques de 30 minutos, si la cédula no tiene de 6 a 10 dígitos (semana 6) o si el médico está vacío, pasa de 100 caracteres o tiene caracteres de control (semana 6). |
| Funciones auxiliares | `estaOcupado(string $medico, DateTimeImmutable $inicio): bool` y `totalCitas(): int`. |

**Supuestos**

- Las citas duran 30 minutos y los bloques empiezan en :00 y :30 (la cliente dijo que las citas son de 30 minutos).
- Desde la semana 6 la agenda valida la cédula (solo dígitos, de 6 a 10) y el nombre del médico (no vacío, máximo 100 caracteres, sin saltos de línea ni caracteres de control), para que no entren fragmentos de SQL o HTML cuando se conecte la base de datos.
- La ocupación se calcula por médico: dos médicos distintos pueden tener cita a la misma hora.
- Esta versión guarda las citas en memoria; la persistencia en PostgreSQL se conecta en una entrega posterior.
- No se valida el horario laboral ni las fechas pasadas en esta versión.
- `estaOcupado` espera la hora de inicio de un bloque (:00 o :30); con una hora intermedia, como 9:15, devuelve `false`. Para registrar citas no importa, porque `registrarCita` rechaza esas horas antes de llamarla.

**Casos límite**

- Agenda vacía: el primer registro siempre es válido.
- Una cita que empieza justo cuando termina otra (9:00 y 9:30) **no** es un choque.
- Primer bloque del día (8:00) y último (17:30) son válidos.
- Un intento fallido no crea ninguna cita.

**Ejemplos**

| # | Entrada | Salida |
|---|---|---|
| 1 | `registrarCita("1075234567", "Dra. Torres", 2026-10-28 09:00)` con agenda vacía | `Cita` con estado «Programada»; `estaOcupado("Dra. Torres", 09:00)` es `true` |
| 2 | `registrarCita("1083456789", "Dra. Torres", 2026-10-28 09:00)` cuando ya hay cita a las 9:00 | `HorarioOcupadoException`: «Ese horario ya está ocupado» |
| 3 | `registrarCita("1083456789", "Dra. Torres", 2026-10-28 09:30)` cuando hay cita a las 9:00 | `Cita` con estado «Programada» (bloques contiguos) |

### Cómo ejecutar las pruebas

```bash
composer install
./vendor/bin/phpunit tests/Unit/AgendaRegistrarCitaTest.php
```

## Pruebas de la semana 7

Las reglas, las tablas de casos (particiones, límites y tablas de decisión), las pruebas de la IA revisadas, los errores a propósito y los datos sintéticos están en [`registro_pruebas.md`](registro_pruebas.md). Las pruebas están en `tests/Unit/` y los datos en `tests/Fixtures/datos_sinteticos.json`.

```bash
./vendor/bin/phpunit tests/Unit
```
