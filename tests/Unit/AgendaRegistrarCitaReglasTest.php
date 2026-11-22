<?php

namespace Tests\Unit;

use App\Agenda\Agenda;
use App\Agenda\HorarioOcupadoException;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Semana 7. Regla R1: Agenda::registrarCita (función con varias condiciones).
 *
 * Regla (oráculo, tomada del criterio de aceptación de HU-06 y del contrato del README):
 *  - La hora de inicio debe estar alineada a bloques de 30 minutos: minutos 00 o 30, sin segundos ni fracciones.
 *    Si no lo está -> InvalidArgumentException (se valida antes que la ocupación).
 *  - La ocupación es por médico y por fecha y hora completas: si ese médico ya tiene una cita con ese mismo inicio
 *    -> HorarioOcupadoException «Ese horario ya está ocupado» y no se crea la cita.
 *  - Si pasa las dos condiciones, devuelve una Cita «Programada» y la guarda.
 */
class AgendaRegistrarCitaReglasTest extends TestCase
{
    private const MEDICO = 'Dra. Torres';
    private const OTRO_MEDICO = 'Dr. Cabrera';
    private const CEDULA = '1000000001';
    private const OTRA_CEDULA = '1000000002';

    private Agenda $agenda;

    protected function setUp(): void
    {
        $this->agenda = new Agenda();
    }

    private function lanza(string $claseEsperada, callable $accion): Throwable
    {
        try {
            $accion();
        } catch (Throwable $e) {
            $this->assertSame($claseEsperada, $e::class);

            return $e;
        }

        $this->fail("Debía lanzar $claseEsperada");
    }

    // ---- Particiones válidas y límites de bloque (horas aceptadas) ----

    public static function horasAlineadasProvider(): array
    {
        return [
            // P1 partición válida: minuto 00
            'P1 en punto 09:00' => ['2026-10-28 09:00:00'],
            // P2 partición válida: minuto 30
            'P2 y media 09:30' => ['2026-10-28 09:30:00'],
            // L1 límite: primer bloque del día
            'L1 primer bloque 08:00' => ['2026-10-28 08:00:00'],
            // L2 límite: último bloque del día
            'L2 último bloque 17:30' => ['2026-10-28 17:30:00'],
        ];
    }

    #[DataProvider('horasAlineadasProvider')]
    public function test_acepta_horas_alineadas_a_bloques_de_30_minutos(string $inicio): void
    {
        $cita = $this->agenda->registrarCita(self::CEDULA, self::MEDICO, new DateTimeImmutable($inicio));

        $this->assertSame('Programada', $cita->estado);
        $this->assertSame(self::CEDULA, $cita->cedulaPaciente);
        $this->assertSame(self::MEDICO, $cita->medico);
        $this->assertSame($inicio, $cita->inicio->format('Y-m-d H:i:s'));
        $this->assertSame(1, $this->agenda->totalCitas());
    }

    // ---- Particiones inválidas y límites de bloque (horas rechazadas) ----

    public static function horasNoAlineadasProvider(): array
    {
        return [
            // P3 partición inválida: minuto intermedio
            'P3 minuto intermedio 09:15' => ['2026-10-28 09:15:00'],
            // P4 partición inválida: segundos distintos de cero
            'P4 segundos 09:00:30' => ['2026-10-28 09:00:30'],
            // L3 límite: último valor antes del bloque :30
            'L3 minuto 29 (09:29)' => ['2026-10-28 09:29:00'],
            // L4 límite: primer valor después del bloque :30
            'L4 minuto 31 (09:31)' => ['2026-10-28 09:31:00'],
            // L5 límite: primer valor después del bloque :00
            'L5 minuto 01 (09:01)' => ['2026-10-28 09:01:00'],
            // L6 límite: último valor antes del siguiente :00
            'L6 minuto 59 (09:59)' => ['2026-10-28 09:59:00'],
            // L7 límite: un segundo después del bloque
            'L7 un segundo (09:00:01)' => ['2026-10-28 09:00:01'],
            // L8 límite: una fracción de segundo después del bloque
            'L8 un microsegundo (09:00:00.000001)' => ['2026-10-28 09:00:00.000001'],
        ];
    }

    #[DataProvider('horasNoAlineadasProvider')]
    public function test_rechaza_horas_no_alineadas_y_no_crea_la_cita(string $inicio): void
    {
        $this->lanza(InvalidArgumentException::class, fn () => $this->agenda->registrarCita(
            self::CEDULA,
            self::MEDICO,
            new DateTimeImmutable($inicio),
        ));

        $this->assertSame(0, $this->agenda->totalCitas());
    }

    // ---- Tabla de decisión: hora alineada × mismo médico × misma fecha y hora ----

    /**
     * Cada fila: citas ya existentes [médico, inicio], intento [médico, inicio],
     * excepción esperada (null si se registra) y total de citas esperado al final.
     */
    public static function tablaDecisionProvider(): array
    {
        $torres = 'Dra. Torres';
        $cabrera = 'Dr. Cabrera';

        return [
            // D1 hora alineada = No (no importan las otras condiciones) -> InvalidArgumentException
            'D1 no alineada' => [[[$torres, '2026-10-28 09:00:00']], $torres, '2026-10-28 09:15:00', InvalidArgumentException::class, 1],
            // D2 alineada = Sí, mismo médico = Sí, misma fecha y hora = Sí -> HorarioOcupadoException
            'D2 mismo médico, misma hora' => [[[$torres, '2026-10-28 09:00:00']], $torres, '2026-10-28 09:00:00', HorarioOcupadoException::class, 1],
            // D3 alineada = Sí, mismo médico = Sí, misma hora = No (bloque contiguo) -> se registra
            'D3 mismo médico, bloque contiguo' => [[[$torres, '2026-10-28 09:00:00']], $torres, '2026-10-28 09:30:00', null, 2],
            // D4 alineada = Sí, mismo médico = Sí, misma hora = No (mismo reloj, otro día) -> se registra
            'D4 mismo médico, mismo reloj otro día' => [[[$torres, '2026-10-28 09:00:00']], $torres, '2026-10-29 09:00:00', null, 2],
            // D5 alineada = Sí, mismo médico = No, misma hora = Sí -> se registra (ocupación por médico)
            'D5 otro médico, misma hora' => [[[$torres, '2026-10-28 09:00:00']], $cabrera, '2026-10-28 09:00:00', null, 2],
            // D6 alineada = Sí, mismo médico = No, misma hora = No -> se registra
            'D6 otro médico, otra hora' => [[[$torres, '2026-10-28 09:00:00']], $cabrera, '2026-10-28 10:00:00', null, 2],
            // D7 agenda vacía: el primer registro siempre es válido
            'D7 agenda vacía' => [[], $torres, '2026-10-28 09:00:00', null, 1],
        ];
    }

    #[DataProvider('tablaDecisionProvider')]
    public function test_tabla_de_decision_de_registrar_cita(
        array $existentes,
        string $medico,
        string $inicio,
        ?string $excepcion,
        int $totalEsperado,
    ): void {
        foreach ($existentes as [$medicoExistente, $inicioExistente]) {
            $this->agenda->registrarCita(self::OTRA_CEDULA, $medicoExistente, new DateTimeImmutable($inicioExistente));
        }

        if ($excepcion === null) {
            $cita = $this->agenda->registrarCita(self::CEDULA, $medico, new DateTimeImmutable($inicio));
            $this->assertSame('Programada', $cita->estado);
        } else {
            $e = $this->lanza($excepcion, fn () => $this->agenda->registrarCita(self::CEDULA, $medico, new DateTimeImmutable($inicio)));
            if ($excepcion === HorarioOcupadoException::class) {
                $this->assertSame('Ese horario ya está ocupado', $e->getMessage());
            }
        }

        $this->assertSame($totalEsperado, $this->agenda->totalCitas());
    }
}
