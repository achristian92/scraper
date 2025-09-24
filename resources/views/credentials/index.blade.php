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
        :root{
            --brand:#ff5f2b; --brand-600:#ff3d00; --brand-400:#ff7a2b; --brand-50:#fff2eb;
            --ink:#1f2937; --ink-600:#374151; --muted:#6b7280; --line:rgba(0,0,0,.08); --bg:#fff;
            --radius:12px; --radius-lg:14px; --shadow:0 8px 24px rgba(16,24,40,.06); --shadow-soft:0 6px 16px rgba(16,24,40,.06);
            --app-grad-start:#fff7f2; --app-grad-end:#ffffff;
        }
        body { background: linear-gradient(180deg,var(--app-grad-start),var(--app-grad-end)); color: var(--ink); }
        .app-main { max-width: 960px; margin: 0 auto; padding: 1rem; }
        .bbx-card{ background:var(--bg); border-radius:var(--radius-lg); box-shadow:var(--shadow); border:1px solid var(--line); padding:1.25rem; margin-bottom:1.25rem; }
        .form-label{ font-weight:600; font-size:.9rem; color:var(--ink-600); margin-bottom:.35rem; }
        .form-control,.form-select{ border-radius:var(--radius); border:1px solid var(--line); padding:.7rem .9rem; }
        .btn-brand{ background:linear-gradient(180deg,var(--brand-400),var(--brand-600)); border:none; color:#fff; font-weight:800; border-radius:var(--radius); }
        .btn-brand:hover{ filter:brightness(.95); }
        .btn-outline-brand{ border:1px solid var(--line); background:#fff; color:var(--ink); border-radius:var(--radius); }
        .badge.text-bg-light{ background:var(--brand-50)!important; color:var(--brand)!important; border:1px solid rgba(255,63,0,.12); }
        .seg{ display:inline-flex; gap:4px; padding:4px; border:1px solid var(--line); border-radius:999px; background:#fff; box-shadow:var(--shadow-soft); }
        .seg__btn{ border:0; background:transparent; padding:.4rem .85rem; border-radius:999px; color:var(--ink-600); font-weight:600; }
        .seg__btn.is-active{ background:var(--brand-50); color:var(--brand); }
        .appbar{ position:sticky; top:0; z-index:1030; display:flex; align-items:center; justify-content:space-between; padding:.75rem 1rem; background:linear-gradient(90deg,var(--brand-400),var(--brand-600)); color:#fff; border-radius:var(--radius-lg); box-shadow:var(--shadow); margin-bottom:1rem; }
        .appbar__left{ display:flex; align-items:center; gap:.75rem; }
        .appbar__title{ margin:0; font-size:1.05rem; font-weight:800; letter-spacing:.2px; }
        .appbar__back{ background:#fff; color:var(--ink); border:none; border-radius:var(--radius); padding:.45rem .8rem; font-weight:700; line-height:1; box-shadow:0 2px 6px rgba(0,0,0,.06); }
        .appbar__back:hover{ filter:brightness(.95); }
        @media (max-width:576px){ .appbar{ border-radius:0 0 var(--radius-lg) var(--radius-lg); } .appbar__title{ font-size:1rem; } }
        .qr-scanner{ background:var(--bg); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow-soft); }
        .qr-video-wrap{ border-radius:var(--radius); background:#000; aspect-ratio:16/9; overflow:hidden; }
        .qr-video-wrap video{ width:100%; height:100%; object-fit:cover; }
        .req{ color:var(--brand-600); }
    </style>
</head>

<body class="container">
   <header class="appbar">
        <div class="appbar__left">
            <button type="button" class="btn-back appbar__back" aria-label="Volver">↩︎</button>
            <h1 class="appbar__title">Evolve • Registro</h1>
        </div>
        <div class="appbar__right">
            <span class="badge text-bg-light">Expoalimentaria 2025</span>
        </div>
    </header>


    <main class="app-main">

        {{-- Paso 1 · Captura del link --}}
        <section class="bbx-card">
            <h5 class="mb-3">Paso 1 · Captura del link</h5>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('credentials.extract') }}">
                @csrf
                <div class="row g-3 align-items-end">

                {{-- Asesor --}}
                <div class="col-12">
                    <label class="form-label">Asesor <span class="req">*</span></label>
                    <select name="worker_id" class="form-select" required>
                    <option value="">-- Seleccione --</option>
                    @foreach ($workers as $w)
                        <option value="{{ $w->id }}" @if (!empty($selectedWorkerId) && (int)$selectedWorkerId === $w->id) selected @endif>
                        {{ $w->full_name }} @if ($w->document) ({{ $w->document }}) @endif
                        </option>
                    @endforeach
                    </select>
                    @error('worker_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Segmentador: Escanear / Completar --}}
                <div class="col-12">
                    <div class="seg mb-2" role="tablist" aria-label="Modo de captura">
                    <button type="button"
                            class="seg__btn is-active"
                            role="tab"
                            aria-selected="true"
                            aria-controls="qrScanner"
                            id="tab-scan">
                        Escanear QR
                    </button>
                    <button type="button"
                            class="seg__btn"
                            role="tab"
                            aria-selected="false"
                            aria-controls="sourceUrlInput"
                            id="tab-form">
                        Completar formulario
                    </button>
                    </div>
                </div>

                {{-- URL de origen --}}
                <div class="col-12">
                    <label class="form-label">Link de origen <span class="req">*</span></label>
                    <input type="url"
                        name="source_url"
                        id="sourceUrlInput"
                        class="form-control"
                        placeholder="Pega o escanea el link…"
                        required
                        value="{{ $sourceUrl ?? '' }}">
                    @error('source_url')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Sub-card: Scanner QR (se muestra al elegir “Escanear QR”) --}}
                <div class="col-12">
                    <div id="qrScanner" class="qr-scanner d-none mt-1 p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <strong>Escáner QR</strong>
                        <button id="btnStop" type="button" class="btn btn-outline-brand btn-sm">✖ Cerrar</button>
                    </div>

                    <div class="qr-video-wrap">
                        <video id="qrVideo" playsinline></video>
                        <canvas id="qrCanvas" class="d-none"></canvas>
                    </div>

                    <div id="qrStatus" class="small mt-2 text-muted" aria-live="polite">
                        Apunta la cámara al código QR…
                    </div>
                    </div>
                </div>

                {{-- CTA: Extraer --}}
                <div class="col-12">
                    <button type="submit" class="btn btn-brand px-4 py-2">Extraer</button>
                </div>

                </div>
            </form>

            {{-- Validación suave: deshabilita “Extraer” hasta tener URL válida --}}
            <script>
                (function(){
                const urlInput = document.getElementById('sourceUrlInput');
                const extractBtn = document.querySelector('form[action="{{ route('credentials.extract') }}"] button[type="submit"]');
                if (!urlInput || !extractBtn) return;
                const urlRe = /^(https?:\/\/)[^\s]+$/i;

                function toggleExtract() {
                    extractBtn.disabled = !urlRe.test(urlInput.value.trim());
                }
                urlInput.addEventListener('input', toggleExtract);
                toggleExtract(); // estado inicial
                })();
            </script>
        </section>


        {{-- Paso 2 · Confirmar y guardar --}}
        @isset($extracted)
        <section class="bbx-card">
            <h5 class="mb-3">Paso 2 · Confirmar y guardar</h5>

            <form method="POST" action="{{ route('credentials.store') }}">
                @csrf

                <div class="row g-3">
                {{-- Asesor (reutiliza el valor del Paso 1 si existe) --}}
                <div class="col-12">
                    <label class="form-label">Asesor <span class="req">*</span></label>
                    <select id="store_worker_id_select" name="worker_id" class="form-select" required>
                    <option value="">-- Seleccione --</option>
                    @foreach ($workers as $w)
                        <option value="{{ $w->id }}" @if ((int) old('worker_id', (int) ($selectedWorkerId ?? 0)) === $w->id) selected @endif>
                        {{ $w->full_name }} @if ($w->document) ({{ $w->document }}) @endif
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

                @php
                    $labels = [
                    'TipoCredencial'  => 'Tipo de Credencial',
                    'ApellidoPaterno' => 'Apellido Paterno',
                    'ApellidoMaterno' => 'Apellido Materno',
                    'EmpresaTrabaja'  => 'Empresa',
                    'Telefono'        => 'Teléfono',
                    ];
                @endphp

                {{-- Grid de campos extraídos --}}
                <div class="row g-3">
                    @forelse ($extracted as $name => $value)
                    <div class="col-12 col-md-6">
                        <label class="form-label">{{ $labels[$name] ?? $name }}</label>
                        <input type="text" class="form-control" name="payload[{{ $name }}]" value="{{ $value }}">
                    </div>
                    @empty
                    <div class="col-12">
                        <div class="alert alert-warning mb-0">No se encontraron inputs en la página.</div>
                    </div>
                    @endforelse
                </div>

                <div class="col-12">
                    <small class="text-muted">Revisa los datos antes de guardar.</small>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-brand px-4 py-2">Guardar</button>
                </div>
                </div>
            </form>
        </section>
        @endisset
    </main>

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
        
            //const btnScan   = document.getElementById('btnScan');
            const btnStop   = document.getElementById('btnStop');
            const panel     = document.getElementById('qrScanner');
            const video     = document.getElementById('qrVideo');
            const canvas    = document.getElementById('qrCanvas');
            const statusEl  = document.getElementById('qrStatus');
            const sourceInp = document.getElementById('sourceUrlInput');
            const tabScan = document.getElementById('tab-scan');
            const tabForm = document.getElementById('tab-form');
            //console.log(btnScan);

            if (!panel || !video || !canvas || !statusEl || !sourceInp) return;

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
                video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
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
                } catch (e) {
                    detector = null;
                }
                }

                statusEl.textContent = 'Buscando QR…';
                scanLoop();

            } catch (err) {
                console.error(err);
                // Nuevo comportamiento: mensaje + cambiar a modo Form + cerrar scanner
                statusEl.textContent = 'No se pudo acceder a la cámara (HTTPS o permisos requeridos).';
                try {
                if (typeof activateTab === 'function' && tabForm && tabScan) {
                    activateTab(tabForm, tabScan);
                }
                hideScanner(); // detiene cámara si algo arrancó
                } catch (_) {}
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

            // 1) Extraer SOLO la primera URL (http/https) del texto del QR
            const urlRegex = /(https?:\/\/[^\s]+)/gi;
            const matches = v.match(urlRegex);
            if (!matches || matches.length === 0) {
                // No hay URL: informar y seguir escaneando
                statusEl.textContent = 'No se detectó una URL válida en el QR. Sigue intentando…';
                return;
            }
            const finalVal = matches[0].trim();

            console.log('handleValue filtered:', finalVal);

            // 2) Volcar la URL al campo y disparar eventos por si hay validaciones
            sourceInp.value = finalVal;
            sourceInp.dispatchEvent(new Event('input', { bubbles: true }));
            sourceInp.dispatchEvent(new Event('change', { bubbles: true }));

            // 3) Feedback de estado
            statusEl.textContent = 'QR detectado: ' + finalVal;

            // 4) Cambiar a modo "Formulario" (segmentador) y cerrar el scanner
            //    Requiere que existan tabForm, tabScan, activateTab(), hideScanner()
            try {
                if (typeof activateTab === 'function' && tabForm && tabScan) {
                activateTab(tabForm, tabScan);
                }
                setTimeout(() => { if (typeof hideScanner === 'function') hideScanner(); }, 300);
            } catch (e) {
                console.warn('No se pudo alternar tabs/cerrar scanner:', e);
            }
            document.querySelector('form[action="{{ route('credentials.extract') }}"] button[type="submit"]')
             ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function openScanner()  { startCamera(); }
        function closeScanner() { panel.classList.add('d-none'); stopCamera(); statusEl.textContent = 'Apunta la cámara al código QR…'; }
        function activateTab(activeBtn, inactiveBtn){
            activeBtn.classList.add('is-active');
            activeBtn.setAttribute('aria-selected', 'true');
            inactiveBtn.classList.remove('is-active');
            inactiveBtn.setAttribute('aria-selected', 'false');
        }
        function showScanner(){
            panel.classList.remove('d-none');
            startCamera();
            statusEl.textContent = 'Buscando QR…';
        }
        function hideScanner(){
            panel.classList.add('d-none');
            stopCamera();
            statusEl.textContent = 'Apunta la cámara al código QR…';
        }

        // Eventos
        //btnScan.addEventListener('click', (e) => { e.preventDefault(); openScanner(); });
        btnStop.addEventListener('click', (e) => { e.preventDefault(); closeScanner(); });

        // Tabs del segmentador
        tabScan?.addEventListener('click', () => {
            activateTab(tabScan, tabForm);
            showScanner();
        });
        tabForm?.addEventListener('click', () => {
            activateTab(tabForm, tabScan);
            hideScanner();
        });
        // Si el usuario toca el botón cámara, también activamos pestaña "Escanear QR"
        //btnScan?.addEventListener('click', (e) => {
        //    e.preventDefault();
        //    activateTab(tabScan, tabForm);
        //    showScanner();
        //});
        window.addEventListener('beforeunload', stopCamera);
            })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
    <script>
        document.querySelector('.appbar__back')?.addEventListener('click', () => {
            if (document.referrer) history.back(); else window.location.href = '/';
        });
    </script>

</body>

</html>
