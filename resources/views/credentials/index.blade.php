<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Extractor de Credenciales</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://unpkg.com/@picocss/pico@2/css/pico.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        :root {
            --brand-start: #ff7a2b;
            /* naranja claro */
            --brand-end: #ff3d00;
            /* naranja intenso */
            --brand-plain: #ff5f2b;
        }

        .mono {
            font-family: ui-monospace, Menlo, Consolas, monospace;
        }

        /* Header degradado */
        .bbx-header {
            background: linear-gradient(90deg, var(--brand-start), var(--brand-end));
            color: #fff;
        }

        .bbx-header .btn-back {
            background: #fff;
            border: none;
            border-radius: 12px;
            padding: .5rem 1rem;
            font-weight: 700;
        }

        .bbx-header .title {
            font-weight: 800;
        }

        /* Card/form */
        .bbx-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(16, 24, 40, .06);
            border: 1px solid rgba(0, 0, 0, .04);
        }

        .form-label {
            font-weight: 600;
            color: #555
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, .08);
            padding: .7rem .9rem
        }

        .btn-brand {
            background: linear-gradient(180deg, var(--brand-start), var(--brand-end));
            border: none;
            color: #fff;
            font-weight: 800;
            border-radius: 10px;
        }

        .btn-brand:hover {
            filter: brightness(.95)
        }

        .btn-outline-brand {
            border: 1px solid rgba(0, 0, 0, .08);
            background: #fff;
            color: #333;
            border-radius: 10px;
        }

        .req {
            color: #ff3b00
        }

        @media (min-width: 992px) {
            .bbx-card {
                padding: 1.25rem 1.25rem
            }
        }
    </style>
</head>

<body class="container">
    <div class="bbx-header rounded-4 p-3 mb-3 d-flex align-items-center justify-content-between">
        <button type="button" class="btn-back">↩︎ Volver</button>
        <div class="title">Evolve • Registro</div>
        <span class="badge text-bg-light">Demo UI</span>
    </div>

    <h3 class="mb-3">Extractor de Credenciales</h3>
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('credentials.extract') }}" class="bbx-card p-3 mb-4">
        @csrf
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-12">
                <label class="form-label">Trabajador <span class="req">*</span></label>
                <select name="worker_id" class="form-select" required>
                    <option value="">-- Seleccione --</option>
                    @foreach ($workers as $w)
                        <option value="{{ $w->id }}" @if (!empty($selectedWorkerId) && (int) $selectedWorkerId === $w->id) selected @endif>
                            {{ $w->full_name }} @if ($w->document)
                                ({{ $w->document }})
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('worker_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12 col-lg-12">
                <label class="form-label">Link de origen <span class="req">*</span></label>
                <input type="url" name="source_url" class="form-control" placeholder="https://..." required
                    value="{{ $sourceUrl ?? '' }}">
                @error('source_url')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-brand px-4 py-2">Extraer</button>
            </div>
        </div>
    </form>

    {{-- Si hay datos extraídos, mostramos el formulario editable y botón Guardar --}}
    @isset($extracted)
        <hr>
        <h4 class="mb-2">Datos extraídos</h4>
        <form method="POST" action="{{ route('credentials.store') }}" class="bbx-card p-3 mb-4">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Trabajador (confirmar) <span class="req">*</span></label>
                    <select id="store_worker_id_select" name="worker_id" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        @foreach ($workers as $w)
                            <option value="{{ $w->id }}" @if ((int) old('worker_id', (int) ($selectedWorkerId ?? 0)) === $w->id) selected @endif>
                                {{ $w->full_name }} @if ($w->document)
                                    ({{ $w->document }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('worker_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                    <input type="hidden" id="store_worker_id_hidden" name="worker_id_hidden_copy"
                        value="{{ old('worker_id', $selectedWorkerId) }}">
                    <input type="hidden" name="source_url" value="{{ $sourceUrl }}">
                </div>

                @forelse($extracted as $name => $value)
                    <div class="col-12 col-md-6">
                        <label class="form-label">{{ $name }}</label>
                        <input type="text" class="form-control" name="payload[{{ $name }}]"
                            value="{{ $value }}">
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-warning mb-0">No se encontraron inputs en la página.</div>
                    </div>
                @endforelse

                <div class="col-12"><small class="text-muted">Verifica el trabajador antes de guardar.</small></div>
                <div class="col-12">
                    <button type="submit" class="btn btn-brand px-4 py-2">Guardar</button>
                </div>
            </div>
        </form>
    @endisset
    <script>
        (function() {
            const firstExtractForm = document.querySelector('form[action="{{ route('credentials.extract') }}"]');
            const firstSelect = firstExtractForm ? firstExtractForm.querySelector('select[name="worker_id"]') : null;
            const storeSelect = document.getElementById('store_worker_id_select');
            const hiddenCopy = document.getElementById('store_worker_id_hidden');
            if (firstSelect && storeSelect) {
                // Si el select de guardar está vacío, usa el valor del primer select
                if (!storeSelect.value && firstSelect.value) {
                    storeSelect.value = firstSelect.value;
                }
                const syncHidden = () => {
                    if (hiddenCopy) hiddenCopy.value = storeSelect.value;
                };
                storeSelect.addEventListener('change', syncHidden);
                syncHidden();
            }
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
</body>

</html>
