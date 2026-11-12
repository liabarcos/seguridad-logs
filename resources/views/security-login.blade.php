<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login de seguridad</title>
    <style>
        :root {
            --bg: #0f172a;
            --panel: #16213a;
            --line: #24324f;
            --ink: #e6edf7;
            --muted: #93a4bf;
            --accent: #38bdf8;
            --green: #34d399;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 15% 15%, #1e3a5f 0, transparent 45%),
                radial-gradient(circle at 85% 85%, #1d2f4d 0, transparent 40%),
                var(--bg);
        }
        .card {
            width: 100%;
            max-width: 420px;
            padding: 36px 34px 30px;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: 0 28px 70px rgba(0, 0, 0, .45);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 11px;
            font-size: 12px;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--green);
            background: rgba(52, 211, 153, .1);
            border: 1px solid rgba(52, 211, 153, .35);
            border-radius: 999px;
        }
        .badge::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--green);
        }
        h1 { margin: 18px 0 6px; font-size: 25px; }
        p.sub { margin: 0 0 26px; color: var(--muted); font-size: 14px; line-height: 1.5; }
        label {
            display: block;
            margin: 16px 0 6px;
            font-size: 13px;
            color: var(--muted);
        }
        input {
            width: 100%;
            padding: 12px 13px;
            font-size: 15px;
            color: var(--ink);
            background: #0d1526;
            border: 1px solid var(--line);
            border-radius: 9px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, .16);
        }
        button {
            width: 100%;
            margin-top: 24px;
            padding: 12px;
            font-size: 15px;
            font-weight: 600;
            color: #062033;
            background: linear-gradient(180deg, #7dd3fc, #38bdf8);
            border: 0;
            border-radius: 9px;
            cursor: pointer;
            transition: filter .15s;
        }
        button:hover { filter: brightness(1.08); }
        .foot {
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid var(--line);
            font-size: 12.5px;
            color: var(--muted);
            line-height: 1.6;
        }
        .foot a { color: var(--accent); text-decoration: none; }
        code {
            padding: 1px 5px;
            font-family: Consolas, monospace;
            font-size: 12px;
            background: #0d1526;
            border: 1px solid var(--line);
            border-radius: 4px;
        }
        @media (max-width: 460px) {
            .card { padding: 28px 22px 24px; }
        }
    </style>
</head>
<body>
    <main class="card">
        <span class="badge">Canal security</span>
        <h1>Prueba de autenticación</h1>
        <p class="sub">
            Cada intento queda registrado en <code>login_attempts</code> y en
            el archivo <code>security-*.log</code>.
        </p>

        <form method="POST" action="/security-login">
            @csrf

            <label for="email">Correo</label>
            <input id="email" type="email" name="email" required autofocus
                   autocomplete="username" placeholder="nombre@dominio.com">

            <label for="password">Contraseña</label>
            <input id="password" type="password" name="password" required
                   autocomplete="current-password" placeholder="Tu contraseña">

            <button type="submit">Ingresar</button>
        </form>

        <p class="foot">
            Tras un inicio correcto se devuelve una respuesta JSON. Luego se puede
            abrir el <a href="/security-dashboard">panel de seguridad</a>.
        </p>
    </main>
</body>
</html>
