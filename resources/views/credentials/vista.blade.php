<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Registro con QR</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
body { font-family: system-ui, sans-serif; background:#fafafa; margin:0; padding:1rem; }

    .qr-card {
    background:#fff;
    border-radius:14px;
            box-shadow:0 8px 24px rgba(16,24,40,0.06);
            padding:1rem;
            max-width:720px;
            margin:0 auto;
        }
        .qr-top {
    display:flex; gap:.5rem; align-items:center; margin-bottom:.75rem;
        }
        .qr-selector { flex:1 1 auto; }
        .qr-selector select {
    width:100%; padding:.65rem .75rem;
            border-radius:12px; border:1px solid rgba(0,0,0,0.08);
            background:#f6f6f6; font-weight:600;
        }
        .qr-icon-btn {
    display:inline-flex; align-items:center; justify-content:center;
            width:56px; height:42px;
            border-radius:10px; border:2px dashed rgba(0,0,0,0.06);
            background:#fff; cursor:pointer; font-weight:700;
        }
        .qr-main { margin-top:.5rem; display:flex; gap:.75rem; flex-wrap:wrap; }
        .qr-tab {
    flex:1 1 180px; min-width:180px;
            background:#fff; border-radius:12px;
            padding:.75rem; border:1px solid rgba(0,0,0,0.03);
        }
        .big-btn {
    display:block; width:100%; height:48px;
            border-radius:999px; border:none;
            font-weight:700; font-size:16px; cursor:pointer;
        }
        .btn-orange {
    background:linear-gradient(180deg,#ff6b2a,#ff4a00); color:white;
            box-shadow:0 6px 18px rgba(234,88,12,0.12);
        }
        .btn-outline {
    background:#fff; border:1px solid rgba(0,0,0,0.06); color:#333;
}
    .qr-help { font-size:.85rem; color:#666; margin-top:.5rem; }
    .qr-cta { margin-top:.85rem; }
        .qr-cta button.continue {
    width:100%; padding:.9rem;
            border-radius:10px; border:none; font-weight:800;
            background:linear-gradient(180deg,#ff5f2b,#ff3b00);
            color:white; box-shadow:0 8px 24px rgba(255,75,0,0.12);
        }
    </style>
</head>
<body>

<form method="POST" action="{{ route('credentials.extract') }}">
@csrf
<div class="qr-card">
    <div class="qr-top">
        <div class="qr-selector">
            <label style="font-size:.85rem;color:#666;">Asesor *</label>
            <select name="worker_id" required>
                <option value="">Selecciona...</option>
                @foreach($workers as $w)
                    <option value="{{ $w->id }}">{{ $w->full_name }}</option>
                @endforeach
            </select>
        </div>
        <button id="btn_scan_qr" class="qr-icon-btn" type="button">QR</button>
    </div>

    <div class="qr-main">
        <div class="qr-tab">
            <button id="btn_scan_big" class="big-btn btn-orange" type="button">Escanear QR</button>
            <div class="qr-help">Campos * obligatorios: Nombres, Empresa, Teléfono, Email</div>
        </div>
        <div class="qr-tab">
            <button id="btn_manual" class="big-btn btn-outline" type="button">Completar formulario</button>
            <div class="qr-help">Si lo prefieres, completa manualmente</div>
            <div style="margin-top:.6rem;">
                <input id="source_url_input" name="source_url" type="url" placeholder="https://..." style="width:100%; padding:.6rem; border-radius:10px; border:1px solid rgba(0,0,0,0.06)">
            </div>
        </div>
    </div>

    <div class="qr-cta">
        <button class="continue" type="submit">Continuar</button>
    </div>
</div>
</form>

<!-- Modal cámara -->
<div id="qr_scanner_overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:.5rem; border-radius:8px; width:min(720px,95%);">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <strong>Escanear QR</strong>
            <button id="btn_close_scanner" type="button">✖</button>
        </div>
        <video id="qr_video" autoplay playsinline style="width:100%; height:360px; background:#000; border-radius:6px;"></video>
        <div style="margin-top:.5rem; display:flex; justify-content:space-between; align-items:center;">
            <small id="qr_status">Apunta la cámara al QR…</small>
            <button id="btn_stop_scan" type="button">Detener</button>
        </div>
    </div>
</div>
<script src="https://unpkg.com/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
    (function(){
        const btnScan = document.getElementById('btn_scan_qr');
        const btnScanBig = document.getElementById('btn_scan_big');
        const overlay = document.getElementById('qr_scanner_overlay');
        const video = document.getElementById('qr_video');
        const btnClose = document.getElementById('btn_close_scanner');
        const btnStop = document.getElementById('btn_stop_scan');
        const statusEl = document.getElementById('qr_status');
        const sourceInput = document.getElementById('source_url_input');
        let stream=null, rafId=null, canvas=null, ctx=null, barcodeDetector=null;

        async function startCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'}}});
                video.srcObject=stream; await video.play();
                canvas=document.createElement('canvas'); ctx=canvas.getContext('2d');
                if('BarcodeDetector' in window){ try{barcodeDetector=new BarcodeDetector({formats:['qr_code']});}catch(_){barcodeDetector=null;} }
                scanLoop();
            } catch(e){ statusEl.textContent='No se pudo acceder a la cámara'; }
        }
        function stopCamera(){ if(rafId)cancelAnimationFrame(rafId); if(stream){stream.getTracks().forEach(t=>t.stop());} }
        async function scanLoop(){
            if(!video||video.readyState<2){ rafId=requestAnimationFrame(scanLoop); return;}
            if(barcodeDetector){
                try{ const det=await barcodeDetector.detect(video); if(det.length){onDetect(det[0].rawValue); return;} }catch(_){barcodeDetector=null;}
            } else {
                if(!canvas){canvas=document.createElement('canvas'); ctx=canvas.getContext('2d');}
                canvas.width=video.videoWidth; canvas.height=video.videoHeight;
                ctx.drawImage(video,0,0,canvas.width,canvas.height);
                const img=ctx.getImageData(0,0,canvas.width,canvas.height);
                const code=jsQR(img.data,img.width,img.height);
                if(code) return onDetect(code.data);
            }
            rafId=requestAnimationFrame(scanLoop);
        }
        function onDetect(val){
            sourceInput.value=val; sourceInput.dispatchEvent(new Event('input',{bubbles:true}));
            statusEl.textContent='QR detectado'; setTimeout(closeScanner,600);
        }
        function openScanner(){ overlay.style.display='flex'; startCamera(); }
        function closeScanner(){ overlay.style.display='none'; stopCamera(); }

        btnScan.addEventListener('click',openScanner);
        btnScanBig.addEventListener('click',openScanner);
        btnClose.addEventListener('click',closeScanner);
        btnStop.addEventListener('click',closeScanner);
        overlay.addEventListener('click',(ev)=>{ if(ev.target===overlay)closeScanner(); });
    })();
</script>
</body>
</html>
