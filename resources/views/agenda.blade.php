<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CitaFácil - Agenda del consultorio</title>
    <style>
        :root { --azul:#12355b; --verde:#0e8a7d; --rojo:#c0392b; --gris:#5f6b7a; --fondo:#eef3f8; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: system-ui, "Segoe UI", Arial, sans-serif; background:var(--fondo); color:#1f2933; }
        header { background:var(--azul); color:#fff; padding:18px 32px; border-bottom:4px solid var(--verde); }
        header h1 { margin:0; font-size:26px; }
        header p { margin:4px 0 0; color:#bfd4ea; font-size:14px; }
        main { max-width:980px; margin:24px auto; padding:0 16px; display:grid; gap:20px; }
        section { background:#fff; border-radius:10px; padding:20px 24px; box-shadow:0 1px 3px rgba(0,0,0,.08); }
        h2 { margin:0 0 14px; font-size:18px; color:var(--azul); }
        form.cita { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:12px; align-items:end; }
        label { display:grid; gap:4px; font-size:13px; font-weight:600; color:var(--gris); }
        input, select { padding:9px 10px; border:1px solid #c8d3df; border-radius:6px; font-size:15px; }
        button { padding:9px 16px; border:0; border-radius:6px; font-size:14px; font-weight:600; cursor:pointer; color:#fff; background:var(--verde); }
        button.rojo { background:var(--rojo); }
        button.gris { background:var(--gris); }
        .aviso { padding:10px 14px; border-radius:6px; font-size:15px; }
        .ok { background:#e3f5ee; color:#14684f; }
        .error { background:#fdeaea; color:#9b1c1c; }
        .nota { background:#fff7e0; color:#7a5a00; font-size:14px; }
        table { width:100%; border-collapse:collapse; font-size:15px; }
        th, td { text-align:left; padding:10px 8px; border-bottom:1px solid #e1e8f0; }
        th { color:var(--gris); font-size:13px; }
        .estado { display:inline-block; padding:2px 10px; border-radius:20px; font-size:13px; font-weight:600; }
        .Programada { background:#e0ecfb; color:#1d4f91; }
        .Atendida { background:#e3f5ee; color:#14684f; }
        .Cancelada { background:#fdeaea; color:#9b1c1c; }
        td form { display:inline; }
        .vacio { color:var(--gris); }
    </style>
</head>
<body>
<header>
    <h1>CitaFácil</h1>
    <p>Agenda del consultorio: una cita por médico y por hora, sin cruces</p>
</header>
<main>
    <section class="aviso nota">
        Versión de demostración: las citas se guardan en la sesión del navegador. Todavía no hay inicio de sesión ni base de datos PostgreSQL.
    </section>

    @if (session('ok'))
        <div class="aviso ok" role="status">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="aviso error" role="alert">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="aviso error" role="alert">Revisa los datos del formulario: {{ $errors->first() }}</div>
    @endif

    <section>
        <h2>Registrar una cita</h2>
        <form class="cita" method="post" action="{{ route('citas.store') }}">
            @csrf
            <label>Cédula del paciente
                <input name="cedula" value="{{ old('cedula') }}" inputmode="numeric" placeholder="1000000001" required>
            </label>
            <label>Médico
                <input name="medico" value="{{ old('medico', 'Dra. Marcela Torres') }}" maxlength="100" required>
            </label>
            <label>Fecha
                <input type="date" name="fecha" value="{{ old('fecha', date('Y-m-d')) }}" required>
            </label>
            <label>Hora (bloques de 30 min)
                <select name="hora">
                    @foreach ($horas as $hora)
                        <option value="{{ $hora }}" @selected(old('hora') === $hora)>{{ $hora }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit">Registrar cita</button>
        </form>
    </section>

    <section>
        <h2>Agenda ({{ count($citas) }} {{ count($citas) === 1 ? 'cita' : 'citas' }})</h2>
        @if (count($citas) === 0)
            <p class="vacio">Todavía no hay citas. Registra la primera arriba.</p>
        @else
            <table>
                <thead>
                    <tr><th>Fecha y hora</th><th>Médico</th><th>Cédula</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                @foreach ($citas as $cita)
                    <tr>
                        <td>{{ $cita['inicio'] }}</td>
                        <td>{{ $cita['medico'] }}</td>
                        <td>{{ $cita['cedula'] }}</td>
                        <td><span class="estado {{ $cita['estado'] }}">{{ $cita['estado'] }}</span></td>
                        <td>
                            @if ($cita['estado'] === 'Programada')
                                <form method="post" action="{{ route('citas.estado', $cita['id']) }}">
                                    @csrf
                                    <input type="hidden" name="estado" value="Atendida">
                                    <button type="submit">Marcar atendida</button>
                                </form>
                                <form method="post" action="{{ route('citas.estado', $cita['id']) }}">
                                    @csrf
                                    <input type="hidden" name="estado" value="Cancelada">
                                    <button type="submit" class="rojo">Cancelar</button>
                                </form>
                            @else
                                <span class="vacio">Sin acciones (estado final)</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <form method="post" action="{{ route('citas.reiniciar') }}" style="margin-top:14px">
                @csrf
                <button type="submit" class="gris">Vaciar la agenda de la demostración</button>
            </form>
        @endif
    </section>
</main>
</body>
</html>
