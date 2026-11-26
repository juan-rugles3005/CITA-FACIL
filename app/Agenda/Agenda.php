<?php

namespace App\Agenda;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Agenda de citas del consultorio. Guarda las citas en memoria (todavia sin base de datos)
 * y no permite dos citas del mismo medico a la misma hora.
 */
class Agenda
{
    private const BLOQUE_MINUTOS = 30;

    /** Segundos y microsegundos que debe tener una hora de inicio (formato 's.u'). */
    private const SEGUNDOS_EXACTOS = '00.000000';

    /** La cédula son solo dígitos; 6 a 10 cubre las cédulas colombianas. */
    private const PATRON_CEDULA = '/^\d{6,10}$/D';

    private const MAX_LONGITUD_MEDICO = 100;

    /** @var list<Cita> */
    private array $citas = [];

    /**
     * Registra una cita nueva con estado Programada si el medico esta libre a esa hora.
     *
     * @param  string  $cedulaPaciente  Cedula del paciente: solo digitos, de 6 a 10.
     * @param  string  $medico  Nombre del medico: no vacio, hasta 100 caracteres, sin caracteres de control.
     * @param  DateTimeImmutable  $inicio  Inicio de la cita: minuto 00 o 30, sin segundos ni fracciones.
     * @return Cita La cita creada, que tambien queda guardada en la agenda.
     *
     * @throws InvalidArgumentException si la cedula o el medico no son validos, o la hora no esta alineada
     *                                  a bloques de 30 minutos (en ese orden de validacion).
     * @throws HorarioOcupadoException si el medico ya tiene una cita con ese mismo inicio.
     */
    public function registrarCita(
        string $cedulaPaciente,
        string $medico,
        DateTimeImmutable $inicio,
    ): Cita {
        $this->validarCedula($cedulaPaciente);
        $this->validarMedico($medico);
        $this->validarInicioEnBloque($inicio);

        if ($this->estaOcupado($medico, $inicio)) {
            throw new HorarioOcupadoException('Ese horario ya está ocupado');
        }

        $cita = new Cita($cedulaPaciente, $medico, $inicio);
        $this->citas[] = $cita;

        return $cita;
    }

    private function validarCedula(string $cedulaPaciente): void
    {
        if (preg_match(self::PATRON_CEDULA, $cedulaPaciente) !== 1) {
            throw new InvalidArgumentException('La cédula debe tener entre 6 y 10 dígitos');
        }
    }

    private function validarMedico(string $medico): void
    {
        if (trim($medico) === '') {
            throw new InvalidArgumentException('El nombre del médico no puede estar vacío');
        }

        if (mb_strlen($medico) > self::MAX_LONGITUD_MEDICO) {
            throw new InvalidArgumentException(
                sprintf('El nombre del médico no puede pasar de %d caracteres', self::MAX_LONGITUD_MEDICO)
            );
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $medico) === 1) {
            throw new InvalidArgumentException('El nombre del médico no puede tener caracteres de control');
        }
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

    /**
     * Indica si el medico ya tiene una cita que empieza exactamente en esa fecha y hora.
     * Compara solo el inicio exacto: con una hora intermedia (por ejemplo 9:15) devuelve false.
     *
     * @param  string  $medico  Nombre del medico, comparado de forma exacta (distingue mayusculas).
     * @param  DateTimeImmutable  $inicio  Fecha y hora de inicio del bloque a consultar.
     * @return bool true si hay una cita de ese medico a esa hora; false en cualquier otro caso.
     */
    public function estaOcupado(string $medico, DateTimeImmutable $inicio): bool
    {
        foreach ($this->citas as $cita) {
            if ($cita->medico === $medico && $cita->inicio == $inicio) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cuenta las citas guardadas en la agenda.
     *
     * @return int Numero de citas registradas (0 si la agenda esta vacia).
     */
    public function totalCitas(): int
    {
        return count($this->citas);
    }
}
