<?php

namespace App\Agenda;

use DateTimeImmutable;
use InvalidArgumentException;

class Agenda
{
    private const BLOQUE_MINUTOS = 30;

    /** @var list<Cita> */
    private array $citas = [];

    public function registrarCita(
        string $cedulaPaciente,
        string $medico,
        DateTimeImmutable $inicio,
    ): Cita {
        $this->validarInicioEnBloque($inicio);

        if ($this->estaOcupado($medico, $inicio)) {
            throw new HorarioOcupadoException('Ese horario ya está ocupado');
        }

        $cita = new Cita($cedulaPaciente, $medico, $inicio);
        $this->citas[] = $cita;

        return $cita;
    }

    private function validarInicioEnBloque(DateTimeImmutable $inicio): void
    {
        if ((int) $inicio->format('i') % self::BLOQUE_MINUTOS !== 0 || $inicio->format('s.u') !== '00.000000') {
            throw new InvalidArgumentException('La hora debe estar alineada a bloques de 30 minutos');
        }
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