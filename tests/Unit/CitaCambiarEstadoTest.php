<?php

namespace Tests\Unit;

use App\Agenda\Cita;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Semana 7. Regla R3: Cita::cambiarEstado.
 *
 * Regla (oráculo; alcance de CitaFácil: estados Programada, Atendida y Cancelada):
 *  - Una cita nueva está «Programada».
 *  - Desde «Programada» puede pasar a «Atendida» o a «Cancelada».
 *  - «Atendida» y «Cancelada» son finales: no admiten ningún cambio.
 *  - Pasar de «Programada» a «Programada» no es un cambio válido.
 *  - Un estado que no sea uno de los tres (incluido el texto vacío) es inválido -> InvalidArgumentException;
 *    se revisa antes que la transición.
 *  - Una transición no permitida -> DomainException.
 *  - La cita original no cambia: devuelve una cita nueva con los mismos datos y el estado nuevo.
 */
class CitaCambiarEstadoTest extends TestCase
{
    private function citaEn(string $estado): Cita
    {
        return new Cita('1000000001', 'Dra. Torres', new DateTimeImmutable('2026-10-28 09:00:00'), $estado);
    }

    private function lanza(string $claseEsperada, callable $accion): void
    {
        try {
            $accion();
        } catch (Throwable $e) {
            $this->assertSame($claseEsperada, $e::class);

            return;
        }

        $this->fail("Debía lanzar $claseEsperada");
    }

    /**
     * Tabla de decisión: estado actual × estado nuevo.
     */
    public static function transicionesProvider(): array
    {
        return [
            // T1 Programada -> Atendida: permitida
            'T1 Programada a Atendida' => ['Programada', 'Atendida', null],
            // T2 Programada -> Cancelada: permitida
            'T2 Programada a Cancelada' => ['Programada', 'Cancelada', null],
            // T3 Programada -> Programada: no es un cambio
            'T3 Programada a Programada' => ['Programada', 'Programada', DomainException::class],
            // T4 Atendida es final: ningún destino
            'T4 Atendida a Programada' => ['Atendida', 'Programada', DomainException::class],
            'T5 Atendida a Atendida' => ['Atendida', 'Atendida', DomainException::class],
            'T6 Atendida a Cancelada' => ['Atendida', 'Cancelada', DomainException::class],
            // T7 Cancelada es final: ningún destino
            'T7 Cancelada a Programada' => ['Cancelada', 'Programada', DomainException::class],
            'T8 Cancelada a Atendida' => ['Cancelada', 'Atendida', DomainException::class],
            'T9 Cancelada a Cancelada' => ['Cancelada', 'Cancelada', DomainException::class],
            // T10 estado nuevo inexistente desde un estado que sí permite cambios
            'T10 Programada a Reprogramada' => ['Programada', 'Reprogramada', InvalidArgumentException::class],
            // T11 estado nuevo inexistente desde un estado final: gana la validación del estado
            'T11 Atendida a Reprogramada' => ['Atendida', 'Reprogramada', InvalidArgumentException::class],
            // T12 texto vacío como estado nuevo
            'T12 Programada a vacío' => ['Programada', '', InvalidArgumentException::class],
        ];
    }

    #[DataProvider('transicionesProvider')]
    public function test_tabla_de_transiciones_de_estado(string $actual, string $nuevo, ?string $excepcion): void
    {
        $cita = $this->citaEn($actual);

        if ($excepcion !== null) {
            $this->lanza($excepcion, fn () => $cita->cambiarEstado($nuevo));
            $this->assertSame($actual, $cita->estado);

            return;
        }

        $this->assertSame($nuevo, $cita->cambiarEstado($nuevo)->estado);
    }

    public function test_el_cambio_devuelve_una_cita_nueva_con_los_mismos_datos_y_no_modifica_la_original(): void
    {
        $original = $this->citaEn('Programada');

        $atendida = $original->cambiarEstado('Atendida');

        $this->assertNotSame($original, $atendida);
        $this->assertSame('Programada', $original->estado);
        $this->assertSame('1000000001', $atendida->cedulaPaciente);
        $this->assertSame('Dra. Torres', $atendida->medico);
        $this->assertSame('2026-10-28 09:00:00', $atendida->inicio->format('Y-m-d H:i:s'));
    }

    public function test_una_cita_nueva_nace_programada(): void
    {
        $cita = new Cita('1000000001', 'Dra. Torres', new DateTimeImmutable('2026-10-28 09:00:00'));

        $this->assertSame('Programada', $cita->estado);
    }
}
