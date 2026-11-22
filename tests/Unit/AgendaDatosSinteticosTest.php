<?php

namespace Tests\Unit;

use App\Agenda\Agenda;
use App\Agenda\HorarioOcupadoException;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Semana 7. Pruebas con datos sintéticos (tests/Fixtures/datos_sinteticos.json).
 * Reglas usadas: R1 (la ocupación es por médico y fecha-hora exactas) y R2 (estaOcupado).
 */
class AgendaDatosSinteticosTest extends TestCase
{
    private static function datos(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/../Fixtures/datos_sinteticos.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function medicosProvider(): array
    {
        $filas = [];
        foreach (self::datos()['medicos'] as $i => $medico) {
            $filas['médico sintético ' . ($i + 1) . ': ' . json_encode($medico, JSON_UNESCAPED_UNICODE)] = [$medico];
        }

        return $filas;
    }

    public static function textosVaciosProvider(): array
    {
        $filas = [];
        foreach (self::datos()['textos_vacios'] as $texto) {
            $filas['texto vacío ' . json_encode($texto)] = [$texto];
        }

        return $filas;
    }

    // Un médico con tilde, ñ, apóstrofo o espacio final se comporta igual que cualquiera: ocupa su horario y lo bloquea.
    #[DataProvider('medicosProvider')]
    public function test_cada_medico_sintetico_ocupa_su_horario_y_no_admite_otra_cita_a_esa_hora(string $medico): void
    {
        $agenda = new Agenda();
        $hora = new DateTimeImmutable('2026-10-28 09:00:00');
        $agenda->registrarCita('1000000001', $medico, $hora);

        $this->assertTrue($agenda->estaOcupado($medico, $hora));

        try {
            $agenda->registrarCita('1000000002', $medico, $hora);
            $this->fail('Debía lanzar HorarioOcupadoException');
        } catch (HorarioOcupadoException $e) {
            $this->assertSame('Ese horario ya está ocupado', $e->getMessage());
        }

        $this->assertSame(1, $agenda->totalCitas());
    }

    // Todos los médicos sintéticos pueden atender a la misma hora, cada uno con un paciente sintético distinto.
    public function test_todos_los_medicos_sinteticos_atienden_a_la_misma_hora(): void
    {
        $datos = self::datos();
        $agenda = new Agenda();
        $hora = new DateTimeImmutable('2026-10-28 09:00:00');

        foreach ($datos['medicos'] as $i => $medico) {
            $agenda->registrarCita($datos['pacientes'][$i]['cedula'], $medico, $hora);
        }

        $this->assertSame(6, $agenda->totalCitas());
        foreach ($datos['medicos'] as $medico) {
            $this->assertTrue($agenda->estaOcupado($medico, $hora));
        }
    }

    // Texto vacío como médico consultado en una agenda sin citas -> false
    #[DataProvider('textosVaciosProvider')]
    public function test_consultar_un_medico_vacio_en_una_agenda_vacia_no_esta_ocupado(string $medico): void
    {
        $agenda = new Agenda();

        $this->assertFalse($agenda->estaOcupado($medico, new DateTimeImmutable('2026-10-28 09:00:00')));
    }
}
