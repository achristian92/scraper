<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contactos Registrados</title>
    <link href="https://unpkg.com/@picocss/pico@2/css/pico.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        :root{
          --brand-start:#ff7a2b; /* naranja claro */
          --brand-end:#ff3d00;   /* naranja intenso */
          --brand-plain:#ff5f2b;
        }
        body.container{max-width:1100px}
        .mono { font-family: ui-monospace, Menlo, Consolas, monospace; }
        .bbx-header{background:linear-gradient(90deg,var(--brand-start),var(--brand-end)); color:#fff;}
        .bbx-header .title{font-weight:800}
        .bbx-card{background:#fff;border-radius:14px;box-shadow:0 8px 24px rgba(16,24,40,.06);border:1px solid rgba(0,0,0,.04);}
        .toolbar .form-control{border-radius:10px}
        .btn-brand{background:linear-gradient(180deg,var(--brand-start),var(--brand-end));border:none;color:#fff;font-weight:700;border-radius:10px}
        .btn-outline-brand{border:1px solid rgba(0,0,0,.08);background:#fff;color:#333;border-radius:10px}
        table.table thead th{position:sticky; top:0; background:#fff; z-index:1}
        table.table tbody tr td, table.table thead th{vertical-align:middle}
        .badge-soft{background:rgba(255,99,71,.12); color:#c64200; border:1px solid rgba(255,99,71,.18)}
        table.table{font-size:0.875rem} /* letra más pequeña en la tabla */
        table.table thead th{font-size:0.8rem}
        table.table tbody td{font-size:0.8rem}
    </style>
</head>
<body class="container">
  <div class="bbx-header rounded-4 p-3 mb-3 d-flex align-items-center justify-content-between">
    <div class="title">Evolve • Contactos</div>
    <span class="badge text-bg-light">Listado</span>
  </div>

  <div class="bbx-card p-3">
    <div class="toolbar row g-2 align-items-center mb-3">
      <div class="col-12 col-md-6">
        <input id="q" type="search" class="form-control" placeholder="Buscar por nombre, empresa, email o teléfono…">
      </div>
      <div class="col-6 col-md-3">
        <button id="btn-export" class="btn btn-outline-brand w-100">⬇️ Exportar CSV (vista)</button>
      </div>
      <div class="col-6 col-md-3 text-md-end">
        <span class="text-muted small" id="count-label">{{ number_format($contacts->total()) }} resultado(s)</span>
      </div>
    </div>

    @if($contacts->count() === 0)
      <div class="alert alert-warning mb-0">No hay contactos registrados aún.</div>
    @else
      <div class="table-responsive" style="max-height:65vh;">
        <table id="contacts-table" class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Trabajador</th>
              <th>Tipo</th>
              <th>Nombres</th>
              <th>Apellidos</th>
              <th>Empresa</th>
              <th>Cargo</th>
              <th>Teléfono/Email</th>
              <th>Fuente</th>
              <th>Creado</th>
            </tr>
          </thead>
          <tbody>
            @foreach($contacts as $c)
              <tr>
                <td class="mono">{{ $c->id }}</td>
                <td>{{ optional($c->worker)->full_name ?? '—' }}</td>
                <td>
                  @if(!empty($c->tipo))
                    <span class="badge badge-soft">{{ $c->tipo }}</span>
                  @else
                    —
                  @endif
                </td>
                <td>{{ $c->nombre ?? '—' }}</td>
                <td>{{ $c->ap_pate ?? '—' }}{{ $c->ap_mat ?? '—' }}</td>
                <td>{{ $c->empresa ?? '—' }}</td>
                <td>{{ $c->cargo ?? '—' }}</td>
                <td>
                  @if($c->telefono)
                    <span class="mono">{{ $c->telefono }}</span><br>
                  @else
                    —
                  @endif
                      @if($c->email)
                          <span class="mono">{{ $c->email }}</span>
                      @else
                          —
                      @endif
                </td>
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
      <div class="mt-3">
        {{ $contacts->links() }}
      </div>
    @endif
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script>
    (function(){
      const q = document.getElementById('q');
      const table = document.getElementById('contacts-table');
      const rows = Array.from(table?.querySelectorAll('tbody tr') || []);
      const countLabel = document.getElementById('count-label');
      function norm(s){ return (s||'').toString().toLowerCase(); }
      function filter(){
        const term = norm(q.value);
        let visible = 0;
        rows.forEach(tr => {
          const txt = norm(tr.innerText);
          const show = !term || txt.includes(term);
          tr.style.display = show ? '' : 'none';
          if(show) visible++;
        });
        if(countLabel) countLabel.textContent = visible + ' resultado(s)';
      }
      q && q.addEventListener('input', filter);
      filter();

      // Copiar
      document.querySelectorAll('button.copy').forEach(btn => {
        btn.addEventListener('click', async () => {
          const val = btn.getAttribute('data-copy') || '';
          if(!val) return;
          try{ await navigator.clipboard.writeText(val); btn.textContent='✅'; setTimeout(()=>btn.textContent='📋',1000);}catch(e){ alert('No se pudo copiar'); }
        });
      });

      // Exportar CSV de la vista filtrada
      document.getElementById('btn-export')?.addEventListener('click', () => {
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        const visibleRows = rows.filter(tr => tr.style.display !== 'none');
        const body = visibleRows.map(tr => Array.from(tr.querySelectorAll('td')).slice(0,12).map(td => '"'+ td.innerText.replaceAll('"','""') +'"').join(','));
        const csv = [headers.slice(0,12).join(','), ...body].join('\n');
        const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = 'contactos.csv'; a.click(); URL.revokeObjectURL(url);
      });
    })();
  </script>
</body>
</html>
