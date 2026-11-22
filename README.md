<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
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
| Lanza | `HorarioOcupadoException` con el mensaje «Ese horario ya está ocupado» si el médico ya tiene una cita a esa hora. `InvalidArgumentException` si la hora no está alineada a bloques de 30 minutos. |
| Funciones auxiliares | `estaOcupado(string $medico, DateTimeImmutable $inicio): bool` y `totalCitas(): int`. |

**Supuestos**

- Las citas duran 30 minutos y los bloques empiezan en :00 y :30 (la cliente dijo que las citas son de 30 minutos).
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
