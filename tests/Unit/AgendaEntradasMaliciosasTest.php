<?php

namespace Tests\Unit;

use App\Agenda\Agenda;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas de abuso: entradas hostiles que un cliente podria mandar cuando
 * la agenda se conecte a la base de datos y a las vistas.
 * Esperado: la agenda las rechaza y no guarda ninguna cita.
 */
class AgendaEntradasMaliciosasTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function cedulasInvalidasProvider(): array
    {
        return [
            'inyeccion SQL' => ["1075234567'; DROP TABLE citas;--"],
            'otra inyeccion SQL' => ["1' OR '1'='1"],
            'etiqueta HTML (XSS)' => ['<script>alert(1)</script>'],
            'vacia' => [''],
            'solo espacios' => ['          '],
            'con letras' => ['10752345AB'],
            'demasiado corta (5 digitos)' => ['10752'],
            'demasiado larga (11 digitos)' => ['10752345678'],
            'salto de linea al final' => ["1075234567\n"],
        ];
    }

    #[DataProvider('cedulasInvalidasProvider')]
    public function test_rechaza_cedulas_invalidas_y_no_crea_la_cita(string $cedula): void
    {
        $agenda = new Agenda();

        try {
            $agenda->registrarCita($cedula, 'Dra. Torres', new DateTimeImmutable('2026-10-28 09:00:00'));
            $this->fail('Debio lanzar InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(0, $agenda->totalCitas());
        }
    }

    /** @return array<string, array{string}> */
    public static function medicosInvalidosProvider(): array
    {
        return [
            'vacio' => [''],
            'solo espacios' => ['   '],
            'salto de linea (inyeccion en logs)' => ["Dra. Torres\nERROR: acceso concedido"],
            'caracter nulo' => ["Dra. Torres\0"],
            'mas de 100 caracteres' => [str_repeat('A', 101)],
        ];
    }

    #[DataProvider('medicosInvalidosProvider')]
    public function test_rechaza_medicos_invalidos_y_no_crea_la_cita(string $medico): void
    {
        $agenda = new Agenda();

        try {
            $agenda->registrarCita('1075234567', $medico, new DateTimeImmutable('2026-10-28 09:00:00'));
            $this->fail('Debio lanzar InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(0, $agenda->totalCitas());
        }
    }

    public function test_acepta_un_medico_de_exactamente_100_caracteres(): void
    {
        $agenda = new Agenda();
        $medico = str_repeat('A', 100);

        $cita = $agenda->registrarCita('1075234567', $medico, new DateTimeImmutable('2026-10-28 09:00:00'));

        $this->assertSame($medico, $cita->medico);
    }
}
