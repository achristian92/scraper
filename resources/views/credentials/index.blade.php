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
        .qr-scanner {
            background: #fff;
            border: 1px solid rgba(0,0,0,.08);
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(16,24,40,.06);
        }
        .qr-video-wrap {
            position: relative;
            width: 100%;
            overflow: hidden;
            border-radius: 10px;
            background: #000;
            aspect-ratio: 16 / 9; /* alto responsivo */
        }
        .qr-video-wrap video {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
                <label class="form-label d-flex align-items-center justify-content-between">
                    <span>Link de origen <span class="req">*</span></span>
                    <button id="btnScan" type="button" class="btn btn-outline-brand btn-sm">
                    📷 Escanear QR
                    </button>
                </label>
                <input type="url" name="source_url" id="sourceUrlInput" class="form-control" placeholder="https://..." required
                    value="{{ $sourceUrl ?? '' }}">
                @error('source_url')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
                <div id="qrScanner" class="qr-scanner d-none mt-3 p-2">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                    <strong>Escáner QR</strong>
                    <div>
                        <button id="btnStop" type="button" class="btn btn-outline-brand btn-sm">✖ Cerrar</button>
                    </div>
                </div>

                <div class="qr-video-wrap">
                    <video id="qrVideo" playsinline></video>
                    <!-- Canvas solo para fallback jsQR; se mantiene oculto -->
                    <canvas id="qrCanvas" class="d-none"></canvas>
                    </div>

                    <div id="qrStatus" class="small mt-2 text-muted">Apunta la cámara al código QR…</div>
                </div>
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
     <!-- Fallback para lectura de QR si no hay BarcodeDetector -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
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
            // Elementos
  const btnScan   = document.getElementById('btnScan');
  console.log(btnScan);
  
  const btnStop   = document.getElementById('btnStop');
  const panel     = document.getElementById('qrScanner');
  const video     = document.getElementById('qrVideo');
  const canvas    = document.getElementById('qrCanvas');
  const statusEl  = document.getElementById('qrStatus');
  const sourceInp = document.getElementById('sourceUrlInput');

  if (!btnScan || !panel || !video || !canvas || !statusEl || !sourceInp) return;

  let stream = null;
  let ctx = null;
  let rafId = null;
  let detector = null;

  const hasDetector = 'BarcodeDetector' in window;

  async function startCamera() {
    // Mostrar panel
    panel.classList.remove('d-none');

    try {
      // pedir cámara trasera si existe
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: { ideal: 'environment' } , width: { ideal: 1280 }, height: { ideal: 720 } },
        audio: false
      });
      video.srcObject = stream;
      await video.play();

      // Canvas para fallback
      ctx = canvas.getContext('2d', { willReadFrequently: true });

      // Intentar API nativa
      if (hasDetector) {
        try {
          detector = new BarcodeDetector({ formats: ['qr_code'] });
        } catch (e) { detector = null; }
      }

      statusEl.textContent = 'Buscando QR…';
      scanLoop();
    } catch (err) {
      console.error(err);
      statusEl.textContent = 'No se pudo acceder a la cámara (requiere HTTPS y permisos).';
    }
  }

  function stopCamera() {
    if (rafId) cancelAnimationFrame(rafId), rafId = null;
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
    try { video.pause(); video.srcObject = null; } catch(e){}
    detector = null;
  }

  async function scanLoop() {
    if (!video || video.readyState < 2) {
      rafId = requestAnimationFrame(scanLoop);
      return;
    }

    // Ajustar canvas al tamaño de video
    if (canvas.width !== video.videoWidth || canvas.height !== video.videoHeight) {
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
    }

    // 1) Intentar con BarcodeDetector
    if (detector) {
      try {
        const found = await detector.detect(video);
        if (found && found.length) {
          const val = (found[0].rawValue || '').trim();
          if (val) return handleValue(val);
        }
      } catch (e) {
        // Si falla la API nativa, pasamos a jsQR
        detector = null;
      }
      rafId = requestAnimationFrame(scanLoop);
      return;
    }

    // 2) Fallback jsQR
    try {
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
      const code = jsQR(img.data, img.width, img.height);
      if (code && code.data) return handleValue(code.data.trim());
    } catch (e) {
      // ignora y sigue
    }
    rafId = requestAnimationFrame(scanLoop);
  }

  function handleValue(v) {
    console.log('handleValue raw:', v);

    // Buscar dentro de la cadena cualquier URL (http o https)
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    const matches = v.match(urlRegex);
    let finalVal = v;

    if (matches && matches.length > 0) {
        finalVal = matches[0]; // Usar la primera URL encontrada
    }

    console.log('handleValue filtered:', finalVal);

    // Asignar al campo
    sourceInp.value = finalVal;
    // Disparar eventos
    sourceInp.dispatchEvent(new Event('input', { bubbles: true }));
    sourceInp.dispatchEvent(new Event('change', { bubbles: true }));
    statusEl.textContent = 'QR detectado: ' + finalVal;

    // Cerrar tras un instante
    setTimeout(closeScanner, 600);
  }


  function openScanner()  { startCamera(); }
  function closeScanner() { panel.classList.add('d-none'); stopCamera(); statusEl.textContent = 'Apunta la cámara al código QR…'; }

  // Eventos
  btnScan.addEventListener('click', (e) => { e.preventDefault(); openScanner(); });
  btnStop.addEventListener('click', (e) => { e.preventDefault(); closeScanner(); });
  window.addEventListener('beforeunload', stopCamera);
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
   

</body>

</html>
