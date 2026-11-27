<?php

namespace Tests\Feature;

use Tests\TestCase;

class AgendaPantallaTest extends TestCase
{
    /** @return array<string, string> */
    private function cita(string $cedula = '1000000001', string $hora = '09:00', string $medico = 'Dra. Torres'): array
    {
        return ['cedula' => $cedula, 'medico' => $medico, 'fecha' => '2026-12-01', 'hora' => $hora];
    }

    public function test_la_pantalla_carga_con_la_agenda_vacia(): void
    {
        $this->get('/')->assertOk()->assertSee('Todavía no hay citas');
    }

    public function test_registrar_una_cita_la_muestra_como_programada(): void
    {
        $this->post('/citas', $this->cita())->assertRedirect();

        $this->get('/')->assertSee('1000000001')->assertSee('Programada')->assertSee('2026-12-01 09:00');
    }

    public function test_la_misma_hora_del_mismo_medico_se_rechaza(): void
    {
        $this->post('/citas', $this->cita('1000000001'));
        $this->post('/citas', $this->cita('1000000002'))->assertSessionHas('error', 'Ese horario ya está ocupado');

        $this->get('/')->assertSee('Agenda (1 cita)', false);
    }

    public function test_otro_medico_si_puede_tener_cita_a_la_misma_hora(): void
    {
        $this->post('/citas', $this->cita('1000000001'));
        $this->post('/citas', $this->cita('1000000002', '09:00', 'Dr. Muñoz'))->assertSessionHas('ok');
    }

    public function test_una_cedula_invalida_se_rechaza(): void
    {
        $this->post('/citas', $this->cita("1' OR '1'='1"))
            ->assertSessionHas('error', 'La cédula debe tener entre 6 y 10 dígitos');
    }

    public function test_una_hora_fuera_de_los_bloques_se_rechaza(): void
    {
        $this->post('/citas', $this->cita('1000000001', '09:15'))->assertSessionHasErrors('hora');
    }

    public function test_el_html_del_medico_se_muestra_escapado(): void
    {
        $this->post('/citas', $this->cita('1000000001', '09:00', '<script>alert(1)</script>'));

        $this->get('/')->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_marcar_atendida_cambia_el_estado_y_ya_no_se_puede_cancelar(): void
    {
        $this->post('/citas', $this->cita());
        $id = $this->idDeLaPrimeraCita();

        $this->post("/citas/{$id}/estado", ['estado' => 'Atendida'])->assertSessionHas('ok');
        $this->get('/')->assertSee('Atendida');

        $this->post("/citas/{$id}/estado", ['estado' => 'Cancelada'])
            ->assertSessionHas('error', 'No se puede pasar de Atendida a Cancelada');
    }

    public function test_cancelar_una_cita_libera_la_hora(): void
    {
        $this->post('/citas', $this->cita('1000000001'));
        $id = $this->idDeLaPrimeraCita();
        $this->post("/citas/{$id}/estado", ['estado' => 'Cancelada']);

        $this->post('/citas', $this->cita('1000000002'))->assertSessionHas('ok');
    }

    private function idDeLaPrimeraCita(): string
    {
        /** @var list<array{id: string}> $citas */
        $citas = session('citas');

        return $citas[0]['id'];
    }
}
