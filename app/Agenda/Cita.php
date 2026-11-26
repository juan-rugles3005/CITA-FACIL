<?php

namespace App\Agenda;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

/**
 * Cita medica inmutable: para cambiar el estado se crea otra con cambiarEstado().
 */
class Cita
{
    public const PROGRAMADA = 'Programada';

    public const ATENDIDA = 'Atendida';

    public const CANCELADA = 'Cancelada';

    private const ESTADOS = [self::PROGRAMADA, self::ATENDIDA, self::CANCELADA];

    /**
     * @param  string  $cedulaPaciente  Cedula del paciente.
     * @param  string  $medico  Nombre del medico.
     * @param  DateTimeImmutable  $inicio  Fecha y hora de inicio de la cita.
     * @param  string  $estado  Programada (por defecto), Atendida o Cancelada. El constructor no lo valida;
     *                          los cambios de estado pasan por cambiarEstado().
     */
    public function __construct(
        public readonly string $cedulaPaciente,
        public readonly string $medico,
        public readonly DateTimeImmutable $inicio,
        public readonly string $estado = self::PROGRAMADA,
    ) {
    }

    /**
     * Devuelve una cita nueva con el estado indicado; la original no cambia.
     * Solo una cita Programada puede pasar a Atendida o Cancelada.
     *
     * @param  string  $nuevoEstado  Estado destino: Programada, Atendida o Cancelada.
     * @return self Cita nueva con los mismos datos y el estado nuevo.
     *
     * @throws InvalidArgumentException si el estado nuevo no existe
     * @throws DomainException si la transición no está permitida
     */
    public function cambiarEstado(string $nuevoEstado): self
    {
        if (! in_array($nuevoEstado, self::ESTADOS, true)) {
            throw new InvalidArgumentException(sprintf('Estado desconocido: "%s"', $nuevoEstado));
        }

        if ($this->estado !== self::PROGRAMADA || $nuevoEstado === self::PROGRAMADA) {
            throw new DomainException(sprintf('No se puede pasar de %s a %s', $this->estado, $nuevoEstado));
        }

        return new self($this->cedulaPaciente, $this->medico, $this->inicio, $nuevoEstado);
    }
}
