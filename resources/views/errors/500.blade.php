<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error del sistema</title>
    <style>
        :root {
            color-scheme: light;
            --gold: #edd77e;
            --gold-dark: #c49a16;
            --ink: #202124;
            --muted: #6f737b;
            --surface: #fffdf6;
            --border: #eee4bf;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background: #f8f8f8;
        }

        .error-card {
            width: min(520px, 100%);
            padding: 34px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--surface);
            box-shadow: 0 18px 55px rgba(32, 33, 36, .08);
            text-align: center;
        }

        .mark {
            width: 64px;
            height: 64px;
            display: inline-grid;
            place-items: center;
            margin-bottom: 18px;
            border-radius: 18px;
            background: var(--gold);
            color: #151515;
            font-size: 30px;
            font-weight: 900;
        }

        h1 {
            margin: 0 0 10px;
            font-size: 26px;
            line-height: 1.2;
        }

        p {
            margin: 0;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.55;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 26px;
        }

        a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
        }

        .primary {
            background: var(--gold);
            color: #151515;
        }

        .secondary {
            color: var(--ink);
            border: 1px solid #d8dce3;
            background: white;
        }
    </style>
</head>
<body>
    <main class="error-card">
        <div class="mark">M</div>
        <h1>Algo no salio como esperabamos</h1>
        <p>
            La operacion pudo haberse registrado correctamente, pero el sistema encontro un problema al terminar el proceso.
            Vuelve al listado y verifica el estado antes de intentarlo otra vez.
        </p>
        <div class="actions">
            <a class="primary" href="{{ url('/visitas') }}">Ir a visitas</a>
            <a class="secondary" href="{{ url('/') }}">Volver al inicio</a>
        </div>
    </main>
</body>
</html>
