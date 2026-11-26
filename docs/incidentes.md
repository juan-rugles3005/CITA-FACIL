# Registro de incidentes

Aquí se anota cada falla en operación: fecha, qué pasó, cómo se resolvió y qué se cambió para que no se repita.

| Fecha | Qué pasó | Cómo se resolvió | Prevención |
|---|---|---|---|
| 2026-11-26 | El pipeline salió en rojo en `main` ([CI #1](https://github.com/juan-rugles3005/CITA-FACIL/actions/runs/45909817751)): `composer audit` encontró 5 avisos de seguridad en 3 paquetes de Symfony | Se actualizó `symfony/*` con `composer update` y se subió `composer.lock` (commit 78f898c); el pipeline volvió a verde ([CI #9](https://github.com/juan-rugles3005/CITA-FACIL/actions/runs/45912132087)) | `composer audit` en cada push y pull request, y Dependabot semanal |
