<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SecurityLoginController extends Controller
{
    /**
     * Procesa un intento de inicio de sesión y aplica las reglas de detección.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $ip = $request->ip();
        $key = 'security:block:'.hash('sha256', $ip);

        // IP bloqueada: no se evalúan credenciales, se responde 429.
        if (Cache::has($key)) {
            $this->record($request, 'blocked');

            Log::channel('security')->warning(
                'Intento desde IP bloqueada',
                ['ip' => $ip]
            );

            return response()->json([
                'message' => 'Acceso temporalmente restringido.',
            ], 429);
        }

        if (Auth::attempt($data)) {
            $request->session()->regenerate();
            $this->record($request, 'success');

            Log::channel('security')->info(
                'Inicio de sesión exitoso',
                ['user_id' => Auth::id(), 'ip' => $ip]
            );

            return response()->json([
                'message' => 'Autenticación correcta',
            ]);
        }

        $this->record($request, 'failed');

        $failedAttempts = LoginAttempt::where('ip_address', $ip)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        Log::channel('security')->warning(
            'Autenticación fallida',
            ['ip' => $ip, 'failed_attempts' => $failedAttempts]
        );

        if ($failedAttempts >= 5) {
            // Bloqueo temporal de 10 minutos.
            Cache::put($key, true, now()->addMinutes(10));

            Log::channel('security')->critical(
                'Bloqueo temporal por intentos fallidos',
                [
                    'ip' => $ip,
                    'failed_attempts' => $failedAttempts,
                    'duration_minutes' => 10,
                ]
            );
        } elseif ($failedAttempts >= 3) {
            Log::channel('security')->alert(
                'Posible ataque de fuerza bruta',
                ['ip' => $ip, 'failed_attempts' => $failedAttempts]
            );
        }

        $this->detectAccountAttack($data['email'], $ip, $failedAttempts);

        return response()->json([
            'message' => 'Credenciales inválidas',
        ], 401);
    }

    /**
     * Mejora: detecta fallos repetidos sobre una misma cuenta.
     *
     * Se registra solo cuando la IP todavía no alcanzó el umbral, para
     * evidenciar un ataque distribuido desde varias direcciones.
     */
    private function detectAccountAttack(string $email, string $ip, int $failedAttempts): void
    {
        $identifier = mb_strtolower($email);

        $accountFailures = LoginAttempt::where('identifier', $identifier)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($accountFailures >= 3 && $failedAttempts < 3) {
            Log::channel('security')->alert(
                'Fallos repetidos sobre una misma cuenta',
                [
                    'identifier' => $identifier,
                    'ip' => $ip,
                    'failed_attempts' => $accountFailures,
                ]
            );
        }
    }

    /**
     * Guarda el evento de auditoría en la base de datos.
     */
    private function record(Request $request, string $status): void
    {
        LoginAttempt::create([
            'identifier' => mb_strtolower((string) $request->input('email', '')),
            'ip_address' => $request->ip(),
            'status' => $status,
            'user_agent' => substr((string) ($request->userAgent() ?? ''), 0, 255),
            'created_at' => now(),
        ]);
    }
}
