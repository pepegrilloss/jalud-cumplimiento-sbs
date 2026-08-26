<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Registro completado · Medlog</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 20px; color: #17221f; background: radial-gradient(circle at top, #fff8d9, #f3f1e9 55%); font-family: "Aptos", "Segoe UI", sans-serif; }
        .card { width: min(100%, 520px); padding: 42px 32px; text-align: center; background: #fffef9; border: 1px solid #dedbd0; border-radius: 24px; box-shadow: 0 24px 70px rgba(23,34,31,.1); }
        img { width: 145px; margin-bottom: 28px; }
        .check { width: 76px; height: 76px; display: grid; place-items: center; margin: 0 auto 24px; color: #17221f; background: #e8c95f; border-radius: 50%; box-shadow: 0 12px 30px rgba(201,163,49,.28); }
        .check svg { width: 38px; height: 38px; }
        h1 { margin: 0; font-family: Georgia, serif; font-size: 37px; letter-spacing: -.03em; }
        p { color: #68736f; font-size: 16px; line-height: 1.55; }
        strong { color: #17221f; }
        .dni { width: 100%; max-height: 330px; margin: 24px 0 18px; border: 1px solid #dedbd0; border-radius: 16px; background: #ebe9e1; object-fit: contain; }
        .access { margin: 18px 0 0; padding: 16px 18px; color: #17603a; background: #e9f8ef; border: 1px solid #b9e7ca; border-radius: 14px; font-size: 17px; font-weight: 800; }
    </style>
</head>
<body>
<main class="card">
    <img src="{{ asset('images/LogoMedlogRC.png') }}" alt="Medlog">
    <div class="check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m5 12 4 4L19 6"/></svg></div>
    <h1>Registro completado</h1>
    <p><strong>{{ $enlace->NOMBRE }}</strong> · {{ $enlace->cliente->RAZON_SOCIAL }}</p>
    <img class="dni" src="{{ route('registro-publico.dni', ['token' => $token]) }}" alt="DNI registrado">
    <p class="access">✓ Registro validado. Ya puede presentarse para ingresar a planta.</p>
</main>
</body>
</html>
