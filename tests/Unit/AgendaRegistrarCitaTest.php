<?php

namespace Tests\Unit;

use App\Agenda\Agenda;
use App\Agenda\HorarioOcupadoException;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * HU-06: registrar una cita en un horario libre sin cruces.
 */
class AgendaRegistrarCitaTest extends TestCase
{
    private const MEDICO = 'Dra. Torres';
    private const CARLOS = '1075234567';
    private const LAURA = '1083456789';

    private Agenda $agenda;

    protected function setUp(): void
    {
        $this->agenda = new Agenda();
    }

    private function hora(string $hhmm): DateTimeImmutable
    {
        return new DateTimeImmutable("2026-10-28 $hhmm");
    }

    // ---- Casos normales ----

    public function test_registra_cita_en_horario_libre_y_la_deja_programada(): void
    {
        $cita = $this->agenda->registrarCita(self::CARLOS, self::MEDICO, $this->hora('09:00'));

        $this->assertSame('Programada', $cita->estado);
        $this->assertSame(self::CARLOS, $cita->cedulaPaciente);
        $this->assertTrue($this->agenda->estaOcupado(self::MEDICO, $this->hora('09:00')));
    }

    public function test_registra_dos_citas_en_horas_distintas_del_mismo_medico(): void
    {
        $this->agenda->registrarCita(self::CARLOS, self::MEDICO, $this->hora('09:00'));
        $this->agenda->registrarCita(self::LAURA, self::MEDICO, $this->hora('10:00'));

        $this->assertSame(2, $this->agenda->totalCitas());
    }

    // ---- Casos límite ----

    public function test_permite_cita_que_empieza_justo_cuando_termina_la_anterior(): void
    {
        $this->agenda->registrarCita(self::CARLOS, self::MEDICO, $this->hora('09:00'));
        $cita = $this->agenda->registrarCita(self::LAURA, self::MEDICO, $this->hora('09:30'));

        $this->assertSame('Programada', $cita->estado);
        $this->assertSame(2, $this->agenda->totalCitas());
    }

    public function test_permite_el_primer_y_el_ultimo_bloque_del_dia(): void
    {
        $this->agenda->registrarCita(self::CARLOS, self::MEDICO, $this->hora('08:00'));
        $this->agenda->registrarCita(self::LAURA, self::MEDICO, $this->hora('17:30'));

        $this->assertTrue($this->agenda->estaOcupado(self::MEDICO, $this->hora('08:00')));
        $this->assertTrue($this->agenda->estaOcupado(self::MEDICO, $this->hora('17:30')));
    }

    // ---- Casos de error ----

    public function test_rechaza_cita_en_horario_ocupado_y_no_crea_la_cita(): void
    {
        $this->agenda->registrarCita(self::CARLOS, self::MEDICO, $this->hora('09:00'));

        try {
            $this->agenda->registrarCita(self::LAURA, self::MEDICO, $this->hora('09:00'));
            $this->fail('Debía lanzar HorarioOcupadoException');
        } catch (HorarioOcupadoException $e) {
            $this->assertSame('Ese horario ya está ocupado', $e->getMessage());
        }

        $this->assertSame(1, $this->agenda->totalCitas());
    }

    public function test_rechaza_hora_que_no_esta_en_bloques_de_30_minutos(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->agenda->registrarCita(self::CARLOS, self::MEDICO, $this->hora('09:15'));
    }
}
