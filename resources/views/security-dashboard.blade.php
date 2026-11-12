<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel de seguridad</title>
    <style>
        :root {
            --bg: #0b1220;
            --panel: #121c2f;
            --line: #22314d;
            --ink: #e6edf7;
            --muted: #93a4bf;
            --accent: #38bdf8;
            --green: #34d399;
            --amber: #fbbf24;
            --red: #f87171;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 34px 22px 60px;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 10% 0, #16273f 0, transparent 40%),
                var(--bg);
        }
        .wrap { max-width: 1140px; margin: 0 auto; }
        header {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: flex-end;
            justify-content: space-between;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--line);
        }
        .tag {
            display: inline-block;
            padding: 5px 10px;
            font-size: 11.5px;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--accent);
            background: rgba(56, 189, 248, .1);
            border: 1px solid rgba(56, 189, 248, .35);
            border-radius: 999px;
        }
        h1 { margin: 10px 0 4px; font-size: 30px; }
        .sub { margin: 0; color: var(--muted); font-size: 14px; }
        .actions { display: flex; gap: 10px; align-items: center; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 15px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--ink);
            background: #1a2740;
            border: 1px solid var(--line);
            border-radius: 9px;
            text-decoration: none;
            cursor: pointer;
        }
        .btn:hover { border-color: var(--accent); }
        .btn.primary {
            color: #062033;
            background: linear-gradient(180deg, #7dd3fc, #38bdf8);
            border-color: transparent;
        }
        .btn.danger { color: var(--red); border-color: rgba(248, 113, 113, .35); }
        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin: 24px 0 30px;
        }
        .stat {
            position: relative;
            padding: 20px 22px;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 13px;
            overflow: hidden;
        }
        .stat::after {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
        }
        .stat.success::after { background: var(--green); }
        .stat.failed::after { background: var(--amber); }
        .stat.blocked::after { background: var(--red); }
        .stat .label {
            font-size: 12px;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .stat .value { margin-top: 8px; font-size: 36px; font-weight: 700; line-height: 1; }
        .stat .hint { margin-top: 8px; font-size: 12px; color: var(--muted); }
        h2 {
            margin: 30px 0 12px;
            font-size: 17px;
            letter-spacing: .01em;
        }
        h2 span { color: var(--muted); font-weight: 400; font-size: 13px; }
        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 13px;
            overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        th {
            padding: 11px 14px;
            text-align: left;
            font-size: 11.5px;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--muted);
            background: #0f1829;
            border-bottom: 1px solid var(--line);
        }
        td { padding: 11px 14px; border-bottom: 1px solid #1b2740; }
        tr:last-child td { border-bottom: 0; }
        tr:hover td { background: rgba(56, 189, 248, .04); }
        .mono { font-family: Consolas, monospace; font-size: 12.5px; }
        .pill {
            display: inline-block;
            padding: 3px 9px;
            font-size: 11.5px;
            font-weight: 600;
            border-radius: 999px;
        }
        .pill.success { color: var(--green); background: rgba(52, 211, 153, .12); }
        .pill.failed { color: var(--amber); background: rgba(251, 191, 36, .12); }
        .pill.blocked { color: var(--red); background: rgba(248, 113, 113, .12); }
        .pill.critical { color: var(--red); background: rgba(248, 113, 113, .14); }
        .pill.alert { color: var(--amber); background: rgba(251, 191, 36, .13); }
        .empty { padding: 22px 16px; color: var(--muted); font-size: 13.5px; }
        .two { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
        .note {
            margin-top: 26px;
            padding: 14px 16px;
            font-size: 12.5px;
            line-height: 1.6;
            color: var(--muted);
            background: rgba(56, 189, 248, .05);
            border: 1px solid rgba(56, 189, 248, .18);
            border-radius: 10px;
        }
        code {
            padding: 1px 5px;
            font-family: Consolas, monospace;
            font-size: 12px;
            background: #0d1526;
            border: 1px solid var(--line);
            border-radius: 4px;
        }
        @media (max-width: 900px) {
            .two { grid-template-columns: 1fr; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <header>
            <div>
                <span class="tag">Monitoreo · últimas 24 h</span>
                <h1>Panel de seguridad</h1>
                <p class="sub">Intentos de autenticación, patrones sospechosos y bloqueos temporales.</p>
            </div>
            <div class="actions">
                <a class="btn primary" href="{{ route('security.export') }}">Exportar evidencias (CSV)</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn danger" type="submit">Salir</button>
                </form>
            </div>
        </header>

        <section class="grid">
            <div class="stat success">
                <div class="label">Accesos exitosos</div>
                <div class="value">{{ $stats['success'] }}</div>
                <div class="hint">Eventos <code>success</code> · nivel INFO</div>
            </div>
            <div class="stat failed">
                <div class="label">Intentos fallidos</div>
                <div class="value">{{ $stats['failed'] }}</div>
                <div class="hint">Eventos <code>failed</code> · nivel WARNING</div>
            </div>
            <div class="stat blocked">
                <div class="label">Intentos bloqueados</div>
                <div class="value">{{ $stats['blocked'] }}</div>
                <div class="hint">Eventos <code>blocked</code> · HTTP 429</div>
            </div>
        </section>

        <h2>IPs sospechosas <span>· 3 o más fallos en los últimos 5 minutos</span></h2>
        <div class="panel">
            @if ($suspiciousIps->isEmpty())
                <div class="empty">Todavía no hay IPs que superen el umbral de 3 fallos en 5 minutos.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>IP</th>
                            <th>Fallos</th>
                            <th>Clasificación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suspiciousIps as $item)
                            <tr>
                                <td class="mono">{{ $item->ip_address }}</td>
                                <td><strong>{{ $item->total }}</strong></td>
                                <td>
                                    @if ($item->total >= 5)
                                        <span class="pill critical">CRÍTICO</span>
                                    @else
                                        <span class="pill alert">ALERTA</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="two">
            <section>
                <h2>Bloqueos recientes <span>· últimos 10 minutos</span></h2>
                <div class="panel">
                    @if ($blockedAttempts->isEmpty())
                        <div class="empty">No hay intentos rechazados por bloqueo temporal.</div>
                    @else
                        <table>
                            <thead>
                                <tr>
                                    <th>IP</th>
                                    <th>Cuenta</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($blockedAttempts as $attempt)
                                    <tr>
                                        <td class="mono">{{ $attempt->ip_address }}</td>
                                        <td>{{ $attempt->identifier ?: '—' }}</td>
                                        <td class="mono">{{ $attempt->created_at?->format('d/m H:i:s') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </section>

            <section>
                <h2>Últimos eventos <span>· 20 registros</span></h2>
                <div class="panel">
                    @if ($recentAttempts->isEmpty())
                        <div class="empty">Sin eventos registrados todavía.</div>
                    @else
                        <table>
                            <thead>
                                <tr>
                                    <th>Estado</th>
                                    <th>IP</th>
                                    <th>Cuenta</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentAttempts as $attempt)
                                    <tr>
                                        <td>
                                            <span class="pill {{ $attempt->status }}">
                                                {{ $attempt->status }}
                                            </span>
                                        </td>
                                        <td class="mono">{{ $attempt->ip_address }}</td>
                                        <td>{{ $attempt->identifier ?: '—' }}</td>
                                        <td class="mono">{{ $attempt->created_at?->format('d/m H:i:s') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </section>
        </div>

        <p class="note">
            Los umbrales son didácticos. Una misma IP puede representar a varios usuarios
            (NAT o red compartida). En producción conviene combinar límites por IP y por
            cuenta, usar un rate limiter atómico y proteger el panel con roles.
        </p>
    </div>
</body>
</html>
