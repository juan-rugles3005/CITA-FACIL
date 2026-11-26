<?php

namespace App\Agenda;

use DateTimeImmutable;
use InvalidArgumentException;

class Agenda
{
    private const BLOQUE_MINUTOS = 30;

    /** Segundos y microsegundos que debe tener una hora de inicio (formato 's.u'). */
    private const SEGUNDOS_EXACTOS = '00.000000';

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
        $minutoAlineado = (int) $inicio->format('i') % self::BLOQUE_MINUTOS === 0;
        $segundosExactos = $inicio->format('s.u') === self::SEGUNDOS_EXACTOS;

        if (! $minutoAlineado || ! $segundosExactos) {
            throw new InvalidArgumentException(
                sprintf('La hora debe estar alineada a bloques de %d minutos', self::BLOQUE_MINUTOS)
            );
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
