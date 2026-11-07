<?php

namespace App\Agenda;

use DateTimeImmutable;
use InvalidArgumentException;

class Agenda
{
    /** @var list<Cita> */
    private array $citas = [];

    public function registrarCita(
        string $cedulaPaciente,
        string $medico,
        DateTimeImmutable $inicio,
    ): Cita {
        if ((int) $inicio->format('i') % 30 !== 0 || $inicio->format('s.u') !== '00.000000') {
            throw new InvalidArgumentException('La hora debe estar alineada a bloques de 30 minutos');
        }

        if ($this->estaOcupado($medico, $inicio)) {
            throw new HorarioOcupadoException('Ese horario ya está ocupado');
        }

        $cita = new Cita($cedulaPaciente, $medico, $inicio);
        $this->citas[] = $cita;

        return $cita;
    }

    public function estaOcupado(string $medico, DateTimeImmutable $inicio): bool
    {
        foreach ($this->citas as $cita) {
            if ($cita->medico === $medico && $cita->inicio == $inicio) {
                return true;
            }
        }

        return false;
    }

    public function totalCitas(): int
    {
        return count($this->citas);
    }
}