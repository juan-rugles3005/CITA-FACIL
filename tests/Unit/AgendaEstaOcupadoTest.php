<?php

namespace Tests\Unit;

use App\Agenda\Agenda;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Semana 7. Regla R2: Agenda::estaOcupado.
 *
 * Regla (oráculo, contrato del README): devuelve true si el médico indicado tiene una cita con ese mismo
 * inicio (fecha y hora exactas); false en cualquier otro caso. Dos médicos distintos no se bloquean entre sí.
 * No se prueba la consulta con hora no alineada (9:15): el README solo documenta que hoy devuelve false,
 * pero la regla del cliente no la define (ver ambigüedades en registro_pruebas.md).
 */
class AgendaEstaOcupadoTest extends TestCase
{
    /**
     * Cada fila: médico de la cita existente, inicio existente, médico consultado, inicio consultado, esperado.
     */
    public static function consultasProvider(): array
    {
        $torres = 'Dra. Torres';
        $cabrera = 'Dr. Cabrera';

        return [
            // P1 mismo médico y mismo inicio -> true
            'P1 mismo médico y mismo inicio' => [$torres, '2026-10-28 09:00:00', $torres, '2026-10-28 09:00:00', true],
            // P2 otro médico, mismo inicio -> false
            'P2 otro médico' => [$torres, '2026-10-28 09:00:00', $cabrera, '2026-10-28 09:00:00', false],
            // P3 mismo médico, mismo reloj en otro día -> false
            'P3 otro día' => [$torres, '2026-10-28 09:00:00', $torres, '2026-10-29 09:00:00', false],
            // L1 límite: el bloque anterior al ocupado -> false
            'L1 bloque anterior 08:30' => [$torres, '2026-10-28 09:00:00', $torres, '2026-10-28 08:30:00', false],
            // L2 límite: el bloque siguiente al ocupado -> false
            'L2 bloque siguiente 09:30' => [$torres, '2026-10-28 09:00:00', $torres, '2026-10-28 09:30:00', false],
        ];
    }

    #[DataProvider('consultasProvider')]
    public function test_estaOcupado_segun_la_regla(
        string $medicoCita,
        string $inicioCita,
        string $medicoConsulta,
        string $inicioConsulta,
        bool $esperado,
    ): void {
        $agenda = new Agenda();
        $agenda->registrarCita('1000000001', $medicoCita, new DateTimeImmutable($inicioCita));

        $this->assertSame($esperado, $agenda->estaOcupado($medicoConsulta, new DateTimeImmutable($inicioConsulta)));
    }

    // P4 agenda vacía -> false
    public function test_agenda_vacia_no_tiene_horarios_ocupados(): void
    {
        $agenda = new Agenda();

        $this->assertFalse($agenda->estaOcupado('Dra. Torres', new DateTimeImmutable('2026-10-28 09:00:00')));
    }

    // P5 con varias citas, solo está ocupado el horario de cada una
    public function test_con_varias_citas_cada_horario_ocupado_se_detecta_y_los_libres_no(): void
    {
        $agenda = new Agenda();
        $agenda->registrarCita('1000000001', 'Dra. Torres', new DateTimeImmutable('2026-10-28 09:00:00'));
        $agenda->registrarCita('1000000002', 'Dra. Torres', new DateTimeImmutable('2026-10-28 10:00:00'));

        $this->assertTrue($agenda->estaOcupado('Dra. Torres', new DateTimeImmutable('2026-10-28 09:00:00')));
        $this->assertTrue($agenda->estaOcupado('Dra. Torres', new DateTimeImmutable('2026-10-28 10:00:00')));
        $this->assertFalse($agenda->estaOcupado('Dra. Torres', new DateTimeImmutable('2026-10-28 09:30:00')));
    }
}
