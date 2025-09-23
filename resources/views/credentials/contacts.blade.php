<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contactos Registrados</title>
    <link href="https://unpkg.com/@picocss/pico@2/css/pico.min.css" rel="stylesheet">
    <style>
        .mono { font-family: ui-monospace, Menlo, Consolas, monospace; }
    </style>
</head>
<body class="container">
<h3>Contactos registrados</h3>

@if($contacts->count() === 0)
    <p>No hay contactos registrados aún.</p>
@else
    <div style="overflow:auto;">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Trabajador</th>
                <th>Tipo</th>
                <th>Nombres</th>
                <th>Ap. Paterno</th>
                <th>Ap. Materno</th>
                <th>Empresa</th>
                <th>Cargo</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Fuente</th>
                <th>Creado</th>
            </tr>
            </thead>
            <tbody>
            @foreach($contacts as $c)
                <tr>
                    <td class="mono">{{ $c->id }}</td>
                    <td>{{ optional($c->worker)->full_name ?? '—' }}</td>
                    <td>{{ $c->tipo ?? '—' }}</td>
                    <td>{{ $c->nombre ?? '—' }}</td>
                    <td>{{ $c->ap_pate ?? '—' }}</td>
                    <td>{{ $c->ap_mat ?? '—' }}</td>
                    <td>{{ $c->empresa ?? '—' }}</td>
                    <td>{{ $c->cargo ?? '—' }}</td>
                    <td>{{ $c->telefono ?? '—' }}</td>
                    <td>{{ $c->email ?? '—' }}</td>
                    <td>
                        @if($c->source_url)
                            <a href="{{ $c->source_url }}" target="_blank" rel="noopener">Ver</a>
                        @else
                            —
                        @endif
                    </td>
                    <td class="mono">{{ $c->created_at?->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div style="margin-top: .75rem;">
        {{ $contacts->links() }}
    </div>
@endif

</body>
</html>
