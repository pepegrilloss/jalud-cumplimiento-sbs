<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Registro de ingreso · Medlog</title>
    <style>
        :root {
            --ink: #111d1a;
            --ink-soft: #34433f;
            --muted: #6a7773;
            --paper: #fffefa;
            --canvas: #f4f2eb;
            --gold: #EDD484;
            --gold-strong: #d7aa20;
            --gold-soft: #fff7dc;
            --line: #e3e0d5;
            --line-strong: #d2cec1;
            --success: #2c8c50;
            --success-bg: #f0faf3;
            --danger: #b42318;
            --danger-bg: #fff1ef;
            --shadow: 0 24px 70px rgba(17, 29, 26, .10);
        }
        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            background:
                radial-gradient(circle at 8% 5%, rgba(237, 212, 132, .24), transparent 28rem),
                linear-gradient(145deg, #faf9f5 0%, var(--canvas) 100%);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }
        button, input { font: inherit; }
        .shell { width: min(100%, 760px); margin: 0 auto; padding: 28px 18px 46px; }
        .brand { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 22px; padding: 0 4px; }
        .brand img { width: 154px; height: auto; }
        .secure { display: inline-flex; align-items: center; gap: 7px; color: #63706c; font-size: 11px; font-weight: 800; letter-spacing: .08em; }
        .secure svg { width: 17px; height: 17px; color: #ad8614; }
        .card {
            overflow: hidden;
            background: var(--paper);
            border: 1px solid rgba(17, 29, 26, .09);
            border-radius: 26px;
            box-shadow: var(--shadow);
        }
        .card-head { padding: 34px 36px 30px; border-bottom: 1px solid var(--line); }
        .eyebrow { margin: 0 0 10px; color: #9a7610; font-size: 11px; font-weight: 900; letter-spacing: .15em; text-transform: uppercase; }
        h1 { margin: 0; font-size: clamp(31px, 7vw, 44px); line-height: 1.05; letter-spacing: -.04em; }
        .intro { max-width: 610px; margin: 14px 0 0; color: var(--muted); font-size: 15px; line-height: 1.6; }
        .company { margin-top: 22px; padding: 15px 17px; background: #f7f3e6; border-left: 4px solid var(--gold-strong); border-radius: 5px 14px 14px 5px; }
        .company small { display: block; margin-bottom: 4px; color: var(--muted); font-size: 10px; font-weight: 900; letter-spacing: .11em; text-transform: uppercase; }
        .company strong { font-size: 17px; line-height: 1.3; }
        form.registration { padding: 34px 36px 36px; }
        .section-head { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
        .step {
            display: inline-grid;
            place-items: center;
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            color: var(--ink);
            background: linear-gradient(145deg, #f5dc87, #e8c449);
            border-radius: 50%;
            box-shadow: 0 6px 15px rgba(204, 160, 29, .18);
            font-size: 14px;
            font-weight: 900;
        }
        .section-title { margin: 0; font-size: 22px; line-height: 1.2; letter-spacing: -.02em; }
        .section-help { margin: 0 0 21px; color: var(--muted); font-size: 14px; line-height: 1.55; }
        .capture-guide { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin: 0 0 22px; }
        .guide-item { display: flex; align-items: center; gap: 10px; min-height: 58px; padding: 12px 14px; color: #42514d; background: #fffdfa; border: 1px solid #ebe5d4; border-radius: 13px; font-size: 13px; font-weight: 700; line-height: 1.35; }
        .guide-check { display: grid; place-items: center; width: 28px; height: 28px; flex: 0 0 28px; color: #9e7710; background: #fff3cf; border-radius: 50%; }
        .guide-check svg { width: 15px; height: 15px; }
        .uploader {
            position: relative;
            min-height: 268px;
            display: grid;
            place-items: center;
            padding: 28px;
            text-align: center;
            background: linear-gradient(145deg, #fffefa, #fffaf0);
            border: 2px dashed #d8b23d;
            border-radius: 20px;
            transition: border-color .2s ease, background .2s ease, box-shadow .2s ease, transform .2s ease;
        }
        .uploader.is-dragging { border-color: #aa8010; background: #fff8dd; box-shadow: 0 0 0 4px rgba(237, 212, 132, .25); transform: translateY(-1px); }
        .file-input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
        .upload-content { width: min(100%, 480px); }
        .upload-icon { position: relative; width: 80px; height: 80px; display: grid; place-items: center; margin: 0 auto 13px; color: var(--ink); background: radial-gradient(circle at 30% 25%, #fff8e1, #f7e7b7); border-radius: 50%; box-shadow: inset 0 0 0 1px rgba(180, 135, 17, .08); }
        .upload-icon svg { width: 38px; height: 38px; }
        .upload-icon .mini { position: absolute; right: 1px; bottom: 2px; width: 27px; height: 27px; display: grid; place-items: center; color: #fff; background: #dba900; border: 3px solid #fffefa; border-radius: 50%; }
        .upload-icon .mini svg { width: 14px; height: 14px; }
        .upload-title { display: block; margin-bottom: 5px; font-size: 19px; font-weight: 900; letter-spacing: -.02em; }
        .upload-meta { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.45; }
        .upload-actions { display: flex; justify-content: center; gap: 10px; margin-top: 18px; }
        .upload-btn { min-height: 44px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 16px; border-radius: 12px; border: 1px solid transparent; cursor: pointer; font-weight: 850; transition: .18s ease; }
        .upload-btn svg { width: 18px; height: 18px; }
        .upload-btn.primary { color: #111d1a; background: linear-gradient(145deg, #f1d97e, #e6bd35); box-shadow: 0 8px 18px rgba(191, 143, 7, .17); }
        .upload-btn.primary:hover { transform: translateY(-1px); box-shadow: 0 10px 22px rgba(191, 143, 7, .23); }
        .upload-btn.secondary { color: #273530; background: #fff; border-color: #ddd8ca; }
        .upload-btn.secondary:hover { background: #f8f6ef; }
        .drop-hint { margin: 13px 0 0; color: #8a938f; font-size: 12px; }
        .preview { display: none; width: 100%; }
        .preview-frame { position: relative; overflow: hidden; min-height: 250px; display: grid; place-items: center; background: #eceae3; border-radius: 14px; }
        .preview img { width: 100%; max-height: 430px; display: block; object-fit: contain; }
        .preview-badge { position: absolute; left: 12px; top: 12px; display: none; align-items: center; gap: 6px; padding: 7px 10px; color: #fff; background: rgba(17, 29, 26, .82); border-radius: 999px; font-size: 11px; font-weight: 900; letter-spacing: .05em; backdrop-filter: blur(8px); }
        .preview-badge svg { width: 14px; height: 14px; }
        .preview-actions { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 12px; }
        .preview-name { min-width: 0; overflow: hidden; color: #596661; font-size: 12px; text-overflow: ellipsis; white-space: nowrap; }
        .change-file { flex: 0 0 auto; padding: 9px 13px; color: #fff; background: #25332f; border: 0; border-radius: 10px; cursor: pointer; font-weight: 800; }
        .ocr-status { display: none; align-items: center; gap: 10px; margin: 18px 0 28px; padding: 13px 15px; border-radius: 12px; font-size: 13px; font-weight: 750; line-height: 1.45; }
        .ocr-status.loading, .ocr-status.error, .ocr-status.success { display: flex; }
        .ocr-status.loading { color: #705813; background: #fff8df; border: 1px solid #ecd582; }
        .ocr-status.error { color: #8e2b22; background: var(--danger-bg); border: 1px solid #f0b5ad; }
        .ocr-status.success { color: #315747; background: var(--success-bg); border: 1px solid #b9e7ca; }
        .status-icon { width: 22px; height: 22px; flex: 0 0 22px; }
        .spinner { width: 20px; height: 20px; flex: 0 0 20px; border: 2px solid rgba(112, 88, 19, .22); border-top-color: #a47b0f; border-radius: 50%; animation: spin .7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .details { margin-top: 4px; padding-top: 0; }
        .form-grid { display: grid; gap: 16px; }
        .form-group label { display: block; margin: 0 0 7px; color: #24312e; font-size: 13px; font-weight: 850; }
        .input-wrap { position: relative; }
        .input-icon { position: absolute; left: 14px; top: 50%; width: 20px; height: 20px; color: #89938f; transform: translateY(-50%); pointer-events: none; }
        .text-input { width: 100%; min-height: 54px; padding: 13px 14px 13px 44px; color: var(--ink); border: 1px solid var(--line-strong); border-radius: 12px; background: #fff; font-size: 15px; outline: none; transition: border-color .18s ease, box-shadow .18s ease, background .18s ease; }
        .text-input::placeholder { color: #a4aba8; }
        .text-input:hover { border-color: #bbb5a7; }
        .text-input:focus { border-color: #caa02b; box-shadow: 0 0 0 4px rgba(237, 212, 132, .24); }
        .error { margin: 9px 0 0; padding: 11px 13px; color: var(--danger); background: var(--danger-bg); border-radius: 10px; font-size: 12px; font-weight: 750; }
        .privacy { display: flex; align-items: flex-start; gap: 13px; margin: 24px 0 20px; padding: 16px 17px; color: var(--muted); background: #fffdf7; border: 1px solid #eadfbd; border-radius: 14px; font-size: 12px; line-height: 1.5; }
        .privacy-icon { width: 32px; height: 32px; display: grid; place-items: center; flex: 0 0 32px; color: #a77d0b; background: #fff4cf; border-radius: 10px; }
        .privacy-icon svg { width: 18px; height: 18px; }
        .privacy strong { display: block; margin-bottom: 2px; color: #24312e; font-size: 13px; }
        .submit { width: 100%; min-height: 56px; display: inline-flex; align-items: center; justify-content: center; gap: 9px; border: 0; border-radius: 14px; background: #17231f; color: #fff; font-size: 16px; font-weight: 850; cursor: pointer; box-shadow: 0 12px 25px rgba(23, 35, 31, .18); transition: .2s ease; }
        .submit:hover { background: #293934; transform: translateY(-1px); }
        .submit:disabled { opacity: .66; cursor: wait; transform: none; }
        .registered { padding: 26px 36px 0; }
        .registered h2 { margin: 0 0 6px; font-size: 18px; }
        .registered p { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.45; }
        .people { margin: 16px 0 0; padding: 0; list-style: none; border: 1px solid var(--line); border-radius: 14px; overflow: hidden; }
        .people li { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 15px; border-bottom: 1px solid var(--line); }
        .people li:last-child { border-bottom: 0; }
        .people strong { font-size: 14px; }
        .people span { color: var(--muted); font-size: 12px; white-space: nowrap; }
        .closed { margin: 28px 36px 34px; padding: 18px; color: #17603a; background: #e9f8ef; border: 1px solid #b9e7ca; border-radius: 14px; font-size: 13px; font-weight: 750; line-height: 1.5; }
        .close-form { padding: 0 36px 34px; }
        .close-button { width: 100%; min-height: 48px; border: 1px solid #8e2b22; border-radius: 12px; color: #8e2b22; background: #fff; font-weight: 800; cursor: pointer; }
        .foot { margin: 20px 0 0; color: var(--muted); text-align: center; font-size: 11px; }
        @media (max-width: 620px) {
            .shell { padding: 18px 10px 30px; }
            .brand { padding: 0 4px; }
            .brand img { width: 132px; }
            .secure { font-size: 10px; }
            .card { border-radius: 21px; }
            .card-head, form.registration { padding: 26px 20px; }
            .registered { padding: 22px 20px 0; }
            .closed { margin: 24px 20px 28px; }
            .close-form { padding: 0 20px 28px; }
            .capture-guide { grid-template-columns: 1fr; gap: 8px; }
            .guide-item { min-height: 52px; }
            .uploader { min-height: 250px; padding: 22px 16px; }
            .upload-actions { flex-direction: column; }
            .upload-btn { width: 100%; }
            .drop-hint { display: none; }
            .section-title { font-size: 20px; }
            .people li { align-items: flex-start; flex-direction: column; gap: 3px; }
        }
    </style>
</head>
<body>
<main class="shell">
    <header class="brand">
        <img src="{{ asset('images/LogoMedlogRC.png') }}" alt="Medlog">
        <span class="secure">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 10V7a5 5 0 0 1 10 0v3"/><rect x="4" y="10" width="16" height="11" rx="2"/></svg>
            ENLACE SEGURO
        </span>
    </header>

    <section class="card">
        <div class="card-head">
            <p class="eyebrow">Registro de visitante</p>
            <h1>Bienvenido a Medlog</h1>
            <p class="intro">Registra a cada persona que ingresará a planta. La lectura del DNI es automática y siempre podrás revisar los datos antes de enviarlos.</p>
            <div class="company">
                <small>Empresa registrada</small>
                <strong>{{ $enlace->cliente->RAZON_SOCIAL }}</strong>
            </div>
        </div>

        @if ($registros->isNotEmpty())
            <section class="registered" aria-label="Personas registradas">
                <h2>{{ $registros->count() }} {{ $registros->count() === 1 ? 'persona registrada' : 'personas registradas' }}</h2>
                <p>Estas personas quedarán disponibles para el control de ingreso en garita.</p>
                <ul class="people">
                    @foreach ($registros as $registro)
                        <li>
                            <strong>{{ $registro->NOMBRES ?: 'Registro anterior' }}</strong>
                            <span>DNI {{ $registro->DNI ?: 'no registrado' }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($enlace->CERRADO_AT)
            <div class="closed" role="status">
                ✓ Visita cerrada el {{ $enlace->CERRADO_AT->format('d/m/Y H:i') }}. Ya no se pueden agregar más personas; garita puede registrar sus ingresos y salidas.
            </div>
        @else
            <form class="registration" method="POST" action="{{ route('registro-publico.store', ['token' => $token]) }}" enctype="multipart/form-data" id="registro-form" data-ocr-url="{{ route('registro-publico.ocr-dni', ['token' => $token]) }}">
                @csrf
                <input type="hidden" name="submission_token" value="{{ $submissionToken }}">

                <section aria-labelledby="step-one-title">
                    <div class="section-head">
                        <span class="step" aria-hidden="true">1</span>
                        <h2 class="section-title" id="step-one-title">Foto o archivo de tu DNI</h2>
                    </div>
                    <p class="section-help">Toma una foto clara o selecciona una imagen/PDF escaneado. Detectaremos automáticamente los datos y podrás corregirlos antes de enviar.</p>

                    <div class="capture-guide" aria-label="Recomendaciones para el documento">
                        <div class="guide-item"><span class="guide-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m5 12 4 4L19 6"/></svg></span><span>DNI completo dentro de la imagen</span></div>
                        <div class="guide-item"><span class="guide-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m5 12 4 4L19 6"/></svg></span><span>Buena luz y sin reflejos</span></div>
                        <div class="guide-item"><span class="guide-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m5 12 4 4L19 6"/></svg></span><span>Foto enfocada y texto legible</span></div>
                        <div class="guide-item"><span class="guide-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m5 12 4 4L19 6"/></svg></span><span>También aceptamos PDF escaneado</span></div>
                    </div>

                    <div class="uploader" id="upload-zone">
                        <input class="file-input" id="dni_foto" name="dni_foto" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" required>

                        <div class="upload-content" id="empty-state">
                            <div class="upload-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.5 5 13 3h-2L9.5 5H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-4.5Z"/><circle cx="12" cy="12.5" r="4"/></svg>
                                <span class="mini"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 19V5"/><path d="m6 11 6-6 6 6"/></svg></span>
                            </div>
                            <span class="upload-title">Carga tu documento</span>
                            <p class="upload-meta">JPG, PNG, WEBP o PDF · máximo 10 MB</p>
                            <div class="upload-actions">
                                <button class="upload-btn primary" type="button" id="camera-button">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 5 13 3h-2L9.5 5H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-4.5Z"/><circle cx="12" cy="12.5" r="4"/></svg>
                                    Tomar foto
                                </button>
                                <button class="upload-btn secondary" type="button" id="file-button">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12"/><path d="m7 8 5-5 5 5"/><path d="M5 15v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"/></svg>
                                    Elegir archivo
                                </button>
                            </div>
                            <p class="drop-hint">También puedes arrastrar y soltar el archivo aquí</p>
                        </div>

                        <div class="preview" id="preview-state">
                            <div class="preview-frame">
                                <img id="preview-image" alt="Vista previa del documento seleccionado">
                                <span class="preview-badge" id="pdf-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>PDF</span>
                            </div>
                            <div class="preview-actions">
                                <span class="preview-name" id="preview-name"></span>
                                <button class="change-file" type="button" id="change-button">Cambiar archivo</button>
                            </div>
                        </div>
                    </div>

                    @error('dni_foto')
                        <p class="error" role="alert">{{ $message }}</p>
                    @enderror

                    <div class="ocr-status" id="ocr-status" role="status" aria-live="polite"></div>
                </section>

                <section class="details" aria-labelledby="step-two-title">
                    <div class="section-head">
                        <span class="step" aria-hidden="true">2</span>
                        <h2 class="section-title" id="step-two-title">Confirma tus datos</h2>
                    </div>
                    <p class="section-help">Revisa cuidadosamente la información detectada antes de enviar el registro.</p>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombres">Nombres completos</label>
                            <div class="input-wrap">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                                <input class="text-input" id="nombres" name="nombres" type="text" value="{{ old('nombres') }}" maxlength="150" autocomplete="name" placeholder="Ej. Juan Carlos Pérez López" required>
                            </div>
                            @error('nombres')
                                <p class="error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="dni">Número de DNI</label>
                            <div class="input-wrap">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="11" r="2"/><path d="M6 16c.7-1.4 1.5-2 2.5-2s1.8.6 2.5 2"/><path d="M14 10h4M14 14h4"/></svg>
                                <input class="text-input" id="dni" name="dni" type="text" value="{{ old('dni') }}" inputmode="numeric" pattern="[0-9]{8}" maxlength="8" autocomplete="off" placeholder="Ej. 12345678" required>
                            </div>
                            @error('dni')
                                <p class="error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <div class="privacy">
                    <span class="privacy-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg></span>
                    <span><strong>Tu información está protegida</strong>Tu documento se almacena de forma privada y solo puede ser consultado por personal autorizado de Medlog. El PDF se convierte a imagen únicamente para realizar la lectura automática.</span>
                </div>

                <button class="submit" type="submit" id="submit-button">
                    Enviar registro
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                </button>
            </form>

            @if ($registros->isNotEmpty())
                <form class="close-form" method="POST" action="{{ route('registro-publico.cerrar', ['token' => $token]) }}" onsubmit="return confirm('¿Deseas cerrar la visita? Luego no podrás registrar más personas con este enlace.');">
                    @csrf
                    <button class="close-button" type="submit">Cerrar visita y enviar a garita</button>
                </form>
            @endif
        @endif
    </section>
    <p class="foot">© {{ date('Y') }} Medlog · Transport & Logistics</p>
</main>

<script>
    const input = document.getElementById('dni_foto');
    const form = document.getElementById('registro-form');

    if (input && form) {
        const uploadZone = document.getElementById('upload-zone');
        const empty = document.getElementById('empty-state');
        const preview = document.getElementById('preview-state');
        const previewImage = document.getElementById('preview-image');
        const previewName = document.getElementById('preview-name');
        const pdfBadge = document.getElementById('pdf-badge');
        const cameraButton = document.getElementById('camera-button');
        const fileButton = document.getElementById('file-button');
        const changeButton = document.getElementById('change-button');
        const button = document.getElementById('submit-button');
        const status = document.getElementById('ocr-status');
        const nombres = document.getElementById('nombres');
        const dni = document.getElementById('dni');
        let ocrController = null;
        let processingId = 0;
        let currentPreviewUrl = null;
        let pdfJsPromise = null;

        const MAX_FILE_SIZE = 10 * 1024 * 1024;
        const PDFJS_URL = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.min.mjs';
        const PDFJS_WORKER_URL = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.worker.min.mjs';

        const escapeHtml = (value) => String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const setOcrStatus = (message, state = '') => {
            status.className = `ocr-status ${state}`.trim();

            if (!message) {
                status.innerHTML = '';
                return;
            }

            if (state === 'loading') {
                status.innerHTML = `<span class="spinner" aria-hidden="true"></span><span>${escapeHtml(message)}</span>`;
                return;
            }

            const icon = state === 'success'
                ? '<svg class="status-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>'
                : '<svg class="status-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16.5h.01"/></svg>';

            status.innerHTML = `${icon}<span>${escapeHtml(message)}</span>`;
        };

        const revokePreview = () => {
            if (currentPreviewUrl) URL.revokeObjectURL(currentPreviewUrl);
            currentPreviewUrl = null;
        };

        const mostrarPreview = (blob, file, esPdf) => {
            revokePreview();
            currentPreviewUrl = URL.createObjectURL(blob);
            previewImage.src = currentPreviewUrl;
            previewName.textContent = file.name;
            pdfBadge.style.display = esPdf ? 'inline-flex' : 'none';
            empty.style.display = 'none';
            preview.style.display = 'block';
        };

        const cargarPdfJs = () => {
            if (!pdfJsPromise) {
                pdfJsPromise = import(PDFJS_URL).then((pdfjsLib) => {
                    pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS_WORKER_URL;
                    return pdfjsLib;
                });
            }

            return pdfJsPromise;
        };

        const canvasToBlob = (canvas, type = 'image/jpeg', quality = 0.95) => new Promise((resolve, reject) => {
            canvas.toBlob((blob) => blob ? resolve(blob) : reject(new Error('No se pudo preparar la imagen.')), type, quality);
        });

        const prepararImagenOcr = async (file) => {
            if (!window.createImageBitmap) return file;

            let bitmap = null;

            try {
                bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
                const maxDimension = Math.max(bitmap.width, bitmap.height);
                if (maxDimension <= 3200 && file.size <= 6 * 1024 * 1024) return file;

                const scale = 3200 / maxDimension;
                const width = Math.max(1, Math.round(bitmap.width * scale));
                const height = Math.max(1, Math.round(bitmap.height * scale));
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const context = canvas.getContext('2d', { alpha: false });
                if (!context) return file;

                context.fillStyle = '#fff';
                context.fillRect(0, 0, width, height);
                context.imageSmoothingEnabled = true;
                context.imageSmoothingQuality = 'high';
                context.drawImage(bitmap, 0, 0, width, height);

                return await canvasToBlob(canvas);
            } catch (_) {
                return file;
            } finally {
                bitmap?.close?.();
            }
        };

        const renderizarPdf = async (file) => {
            const pdfjsLib = await cargarPdfJs();
            const data = new Uint8Array(await file.arrayBuffer());
            const pdf = await pdfjsLib.getDocument({ data }).promise;
            const pagesToRead = Math.min(pdf.numPages, 2);
            const pages = [];

            for (let pageNumber = 1; pageNumber <= pagesToRead; pageNumber += 1) {
                const page = await pdf.getPage(pageNumber);
                const base = page.getViewport({ scale: 1 });
                const maxBaseDimension = Math.max(base.width, base.height);
                const scale = Math.min(4, Math.max(2, 3200 / maxBaseDimension));
                const viewport = page.getViewport({ scale });
                const canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(viewport.width));
                canvas.height = Math.max(1, Math.round(viewport.height));
                const context = canvas.getContext('2d', { alpha: false });

                if (!context) throw new Error('No se pudo procesar el PDF en este dispositivo.');

                context.fillStyle = '#fff';
                context.fillRect(0, 0, canvas.width, canvas.height);
                await page.render({ canvasContext: context, viewport }).promise;

                pages.push({
                    blob: await canvasToBlob(canvas, 'image/jpeg', 0.96),
                    pageNumber,
                    totalPages: pdf.numPages,
                });

                page.cleanup?.();
            }

            await pdf.destroy?.();

            if (!pages.length) throw new Error('El PDF no contiene páginas que podamos leer.');
            return pages;
        };

        const prepararDocumento = async (file) => {
            const esPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');

            if (esPdf) {
                const pages = await renderizarPdf(file);
                mostrarPreview(pages[0].blob, file, true);
                return pages;
            }

            mostrarPreview(file, file, false);
            return [{ blob: await prepararImagenOcr(file), pageNumber: 1, totalPages: 1 }];
        };

        const enviarOcr = async (blob, fileName, signal) => {
            const data = new FormData();
            data.append('dni_foto', blob, fileName);

            const response = await fetch(form.dataset.ocrUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                },
                body: data,
                credentials: 'same-origin',
                signal,
            });

            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.message || 'No se pudo leer el documento.');
            return result;
        };

        const analizarDni = async (file, localProcessingId) => {
            ocrController?.abort();
            ocrController = new AbortController();
            const nombresIniciales = nombres.value;
            const dniInicial = dni.value;
            const esPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');

            try {
                setOcrStatus(esPdf ? 'Preparando PDF escaneado…' : 'Preparando imagen…', 'loading');
                const pages = await prepararDocumento(file);
                if (processingId !== localProcessingId) return;

                let nombresDetectados = '';
                let dniDetectado = '';
                let detected = false;

                for (const page of pages) {
                    if (processingId !== localProcessingId) return;
                    const progress = pages.length > 1 ? ` · página ${page.pageNumber} de ${Math.min(page.totalPages, 2)}` : '';
                    setOcrStatus(`Analizando DNI${progress}…`, 'loading');

                    const result = await enviarOcr(page.blob, `dni-ocr-${page.pageNumber}.jpg`, ocrController.signal);
                    detected = detected || Boolean(result.detected);
                    nombresDetectados ||= result.nombres || '';
                    dniDetectado ||= result.dni || '';

                    if (nombresDetectados && dniDetectado) break;
                }

                if (processingId !== localProcessingId) return;
                if (nombres.value === nombresIniciales) nombres.value = nombresDetectados;
                if (dni.value === dniInicial) dni.value = dniDetectado;

                if (nombresDetectados && dniDetectado) {
                    setOcrStatus('Datos detectados automáticamente. Revísalos y corrige si fuera necesario antes de continuar.', 'success');
                } else if (detected || nombresDetectados || dniDetectado) {
                    setOcrStatus('Detectamos parte de los datos. Completa o corrige los campos antes de enviar.', 'error');
                } else {
                    setOcrStatus('No pudimos detectar los datos. Puedes completar los campos manualmente o probar con otro archivo.', 'error');
                }
            } catch (error) {
                if (error.name === 'AbortError' || processingId !== localProcessingId) return;
                const fallback = esPdf
                    ? 'No se pudo procesar automáticamente el PDF. Puedes completar los datos manualmente o subir una imagen del DNI.'
                    : 'No se pudo leer la imagen. Puedes completar los datos manualmente o probar con otra foto.';
                setOcrStatus(error.message || fallback, 'error');
            }
        };

        const archivoValido = (file) => {
            const permitidos = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            const esExtensionPdf = file.name.toLowerCase().endsWith('.pdf');

            if (!permitidos.includes(file.type) && !esExtensionPdf) {
                setOcrStatus('Formato no permitido. Usa JPG, PNG, WEBP o PDF.', 'error');
                return false;
            }

            if (file.size > MAX_FILE_SIZE) {
                setOcrStatus('El archivo supera el máximo permitido de 10 MB.', 'error');
                return false;
            }

            return true;
        };

        const procesarSeleccion = (file) => {
            if (!file || !archivoValido(file)) {
                input.value = '';
                return;
            }

            processingId += 1;
            const localProcessingId = processingId;
            nombres.value = '';
            dni.value = '';
            analizarDni(file, localProcessingId);
        };

        const abrirCamara = () => {
            input.accept = 'image/*';
            input.setAttribute('capture', 'environment');
            input.click();
        };

        const abrirSelector = () => {
            input.accept = 'image/jpeg,image/png,image/webp,application/pdf';
            input.removeAttribute('capture');
            input.click();
        };

        cameraButton.addEventListener('click', abrirCamara);
        fileButton.addEventListener('click', abrirSelector);
        changeButton.addEventListener('click', abrirSelector);

        input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (file) procesarSeleccion(file);
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            uploadZone.addEventListener(eventName, (event) => {
                event.preventDefault();
                uploadZone.classList.add('is-dragging');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            uploadZone.addEventListener(eventName, (event) => {
                event.preventDefault();
                uploadZone.classList.remove('is-dragging');
            });
        });

        uploadZone.addEventListener('drop', (event) => {
            const file = event.dataTransfer?.files?.[0];
            if (!file || !archivoValido(file)) return;

            try {
                const transfer = new DataTransfer();
                transfer.items.add(file);
                input.files = transfer.files;
            } catch (_) {
                setOcrStatus('Tu navegador no permite adjuntar archivos arrastrándolos. Usa “Elegir archivo”.', 'error');
                return;
            }

            procesarSeleccion(file);
        });

        dni.addEventListener('input', () => {
            dni.value = dni.value.replace(/\D+/g, '').slice(0, 8);
        });

        form.addEventListener('submit', () => {
            ocrController?.abort();
            button.disabled = true;
            button.textContent = 'Enviando…';
        });

        window.addEventListener('beforeunload', revokePreview);
    }
</script>
</body>
</html>
