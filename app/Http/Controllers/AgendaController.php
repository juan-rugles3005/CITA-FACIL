<?php

namespace App\Http\Controllers;

use App\Agenda\Agenda;
use App\Agenda\Cita;
use App\Agenda\HorarioOcupadoException;
use DateTimeImmutable;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Pantalla de demostración de la agenda: usa las clases Agenda y Cita sin cambiarlas.
 * Las citas se guardan en la sesión del navegador porque todavía no hay base de datos.
 */
class AgendaController extends Controller
{
    private const CLAVE_SESION = 'citas';

    private const FORMATO = 'Y-m-d H:i';

    /** Bloques de 30 minutos del horario de la entrevista: 8:00 a 12:00 y 14:00 a 18:00. */
    private const HORAS = [
        '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
        '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30',
    ];

    /** Muestra el formulario y la agenda ordenada por fecha y hora. */
    public function index(Request $request): View
    {
        $citas = $this->citasGuardadas($request);
        usort($citas, fn (array $a, array $b): int => strcmp($a['inicio'], $b['inicio']));

        return view('agenda', ['citas' => $citas, 'horas' => self::HORAS]);
    }

    /** Registra una cita con Agenda::registrarCita y muestra el error si la regla la rechaza. */
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'cedula' => ['required', 'string'],
            'medico' => ['required', 'string'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora' => ['required', sprintf('in:%s', implode(',', self::HORAS))],
        ]);

        $agenda = $this->agendaConLasCitasActivas($this->citasGuardadas($request));
        $inicio = new DateTimeImmutable(sprintf('%s %s:00', $datos['fecha'], $datos['hora']));

        try {
            $agenda->registrarCita($datos['cedula'], $datos['medico'], $inicio);
        } catch (HorarioOcupadoException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $citas = $this->citasGuardadas($request);
        $citas[] = [
            'id' => (string) Str::uuid(),
            'cedula' => $datos['cedula'],
            'medico' => $datos['medico'],
            'inicio' => $inicio->format(self::FORMATO),
            'estado' => Cita::PROGRAMADA,
        ];
        $request->session()->put(self::CLAVE_SESION, $citas);

        return back()->with('ok', 'Cita registrada');
    }

    /** Pasa una cita Programada a Atendida o Cancelada con Cita::cambiarEstado. */
    public function cambiarEstado(Request $request, string $id): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', sprintf('in:%s,%s', Cita::ATENDIDA, Cita::CANCELADA)]]);
        $citas = $this->citasGuardadas($request);

        foreach ($citas as $posicion => $guardada) {
            if ($guardada['id'] !== $id) {
                continue;
            }

            $cita = new Cita(
                $guardada['cedula'],
                $guardada['medico'],
                new DateTimeImmutable(sprintf('%s:00', $guardada['inicio'])),
                $guardada['estado'],
            );

            try {
                $citas[$posicion]['estado'] = $cita->cambiarEstado($datos['estado'])->estado;
            } catch (DomainException $e) {
                return back()->with('error', $e->getMessage());
            } catch (InvalidArgumentException $e) {
                return back()->with('error', $e->getMessage());
            }

            $request->session()->put(self::CLAVE_SESION, $citas);

            return back()->with('ok', 'Estado actualizado');
        }

        return back()->with('error', 'No se encontró la cita');
    }

    /** Borra las citas de la demostración. */
    public function reiniciar(Request $request): RedirectResponse
    {
        $request->session()->forget(self::CLAVE_SESION);

        return back()->with('ok', 'Agenda vacía');
    }

    /**
     * @return list<array{id: string, cedula: string, medico: string, inicio: string, estado: string}>
     */
    private function citasGuardadas(Request $request): array
    {
        /** @var list<array{id: string, cedula: string, medico: string, inicio: string, estado: string}> */
        return array_values((array) $request->session()->get(self::CLAVE_SESION, []));
    }

    /**
     * Una cita cancelada libera la hora, así que no se carga en la agenda.
     *
     * @param  list<array{id: string, cedula: string, medico: string, inicio: string, estado: string}>  $citas
     */
    private function agendaConLasCitasActivas(array $citas): Agenda
    {
        $agenda = new Agenda();

        foreach ($citas as $guardada) {
            if ($guardada['estado'] !== Cita::CANCELADA) {
                $agenda->registrarCita(
                    $guardada['cedula'],
                    $guardada['medico'],
                    new DateTimeImmutable(sprintf('%s:00', $guardada['inicio'])),
                );
            }
        }

        return $agenda;
    }
}
