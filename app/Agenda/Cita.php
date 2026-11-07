<?php

namespace App\Agenda;

use DateTimeImmutable;

class Cita
{
    public function __construct(
        public readonly string $cedulaPaciente,
        public readonly string $medico,
        public readonly DateTimeImmutable $inicio,
        public readonly string $estado = 'Programada',
    ) {
    }
}