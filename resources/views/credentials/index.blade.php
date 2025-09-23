<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Extractor de Credenciales</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://unpkg.com/@picocss/pico@2/css/pico.min.css" rel="stylesheet">
    <style>
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .mono { font-family: ui-monospace, Menlo, Consolas, monospace; }
    </style>
</head>
<body class="container">
<h3>Extractor de Credenciales</h3>

@if(session('status'))
    <article class="success">{{ session('status') }}</article>
@endif

{{-- Formulario: seleccionar trabajador + URL (EXTRAER) --}}
<form method="POST" action="{{ route('credentials.extract') }}">
    @csrf
    <div class="grid">
        <label>
            Trabajador
            <select name="worker_id" required>
                <option value="">-- Seleccione --</option>
                @foreach($workers as $w)
                    <option value="{{ $w->id }}"
                            @if(!empty($selectedWorkerId) && (int)$selectedWorkerId === $w->id) selected @endif>
                        {{ $w->full_name }} @if($w->document) ({{ $w->document }}) @endif
                    </option>
                @endforeach
            </select>
            @error('worker_id') <small class="error">{{ $message }}</small> @enderror
        </label>

        <label>
            Link de origen
            <input type="url" name="source_url" placeholder="https://..." required
                   value="{{ $sourceUrl ?? '' }}">
            @error('source_url') <small class="error">{{ $message }}</small> @enderror
        </label>
    </div>

    <button type="submit">Extraer</button>
</form>

{{-- Si hay datos extraídos, mostramos el formulario editable y botón Guardar --}}
@isset($extracted)
    <hr>
    <h4>Datos extraídos</h4>

    <form method="POST" action="{{ route('credentials.store') }}">
        @csrf
        <label>
            Trabajador (confirmar)
            <select id="store_worker_id_select" name="worker_id" required>
                <option value="">-- Seleccione --</option>
                @foreach($workers as $w)
                    <option value="{{ $w->id }}"
                        @if((int)old('worker_id', (int)($selectedWorkerId ?? 0)) === $w->id) selected @endif>
                        {{ $w->full_name }} @if($w->document) ({{ $w->document }}) @endif
                    </option>
                @endforeach
            </select>
            @error('worker_id') <small class="error">{{ $message }}</small> @enderror
        </label>
        <input type="hidden" id="store_worker_id_hidden" name="worker_id_hidden_copy" value="{{ old('worker_id', $selectedWorkerId) }}">
        <input type="hidden" name="source_url" value="{{ $sourceUrl }}">

        <div class="grid">
            @forelse($extracted as $name => $value)
                <label>
                    {{ $name }}
                    <input type="text" name="payload[{{ $name }}]" value="{{ $value }}">
                </label>
            @empty
                <article class="warning">No se encontraron inputs en la página.</article>
            @endforelse
        </div>
        <small>Verifica el trabajador antes de guardar.</small>

        <button type="submit">Guardar</button>
    </form>
@endisset
<script>
(function(){
  const firstExtractForm = document.querySelector('form[action="{{ route('credentials.extract') }}"]');
  const firstSelect = firstExtractForm ? firstExtractForm.querySelector('select[name="worker_id"]') : null;
  const storeSelect = document.getElementById('store_worker_id_select');
  const hiddenCopy  = document.getElementById('store_worker_id_hidden');
  if (firstSelect && storeSelect) {
    // Si el select de guardar está vacío, usa el valor del primer select
    if (!storeSelect.value && firstSelect.value) {
      storeSelect.value = firstSelect.value;
    }
    const syncHidden = () => { if (hiddenCopy) hiddenCopy.value = storeSelect.value; };
    storeSelect.addEventListener('change', syncHidden);
    syncHidden();
  }
})();
</script>
</body>
</html>
