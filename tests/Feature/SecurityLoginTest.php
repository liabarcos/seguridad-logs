<?php

namespace Tests\Feature;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SecurityLoginTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        Log::spy();
        Log::shouldReceive('channel')->with('security')->andReturnSelf();

        $this->user = User::factory()->create([
            'name' => 'Usuario de prueba',
            'email' => 'usuario@seguridad.test',
            'password' => 'clave-correcta',
        ]);
    }

    private function intentar(string $password, string $ip = '127.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/security-login', [
                'email' => $this->user->email,
                'password' => $password,
            ]);
    }

    public function test_canal_security_esta_configurado(): void
    {
        $this->assertSame('daily', config('logging.channels.security.driver'));
        $this->assertSame('info', config('logging.channels.security.level'));
        $this->assertSame(30, config('logging.channels.security.days'));
    }

    public function test_login_valido_registra_success_y_log_info(): void
    {
        $response = $this->intentar('clave-correcta');

        $response->assertOk()
            ->assertJson(['message' => 'Autenticación correcta']);

        $this->assertDatabaseHas('login_attempts', [
            'identifier' => 'usuario@seguridad.test',
            'ip_address' => '127.0.0.1',
            'status' => 'success',
        ]);

        $this->assertAuthenticatedAs($this->user);

        Log::shouldHaveReceived('info')->withArgs(
            fn (string $message, array $context = []) => $message === 'Inicio de sesión exitoso'
                && $context['user_id'] === $this->user->id
        );
    }

    public function test_dos_fallos_generan_warning(): void
    {
        $this->intentar('incorrecta-1')->assertStatus(401);
        $this->intentar('incorrecta-2')->assertStatus(401);

        $this->assertSame(2, LoginAttempt::where('status', 'failed')->count());

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context = []) => $message === 'Autenticación fallida'
                && $context['failed_attempts'] >= 1
        )->twice();

        Log::shouldNotHaveReceived('alert');
        Log::shouldNotHaveReceived('critical');
    }

    public function test_tres_fallos_generan_alert(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->intentar('incorrecta-'.$i)->assertStatus(401);
        }

        $this->assertSame(3, LoginAttempt::where('status', 'failed')->count());

        Log::shouldHaveReceived('alert')->withArgs(
            fn (string $message, array $context = []) => $message === 'Posible ataque de fuerza bruta'
                && $context['failed_attempts'] === 3
        );
    }

    public function test_cinco_fallos_bloquean_diez_minutos_y_generan_critical(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->intentar('incorrecta-'.$i)->assertStatus(401);
        }

        $key = 'security:block:'.hash('sha256', '127.0.0.1');

        $this->assertTrue(Cache::has($key), 'La IP debería quedar bloqueada en caché.');
        $this->assertSame(5, LoginAttempt::where('status', 'failed')->count());

        Log::shouldHaveReceived('critical')->withArgs(
            fn (string $message, array $context = []) => $message === 'Bloqueo temporal por intentos fallidos'
                && $context['failed_attempts'] === 5
                && $context['duration_minutes'] === 10
        );
    }

    public function test_ip_bloqueada_responde_429_y_registra_blocked(): void
    {
        $key = 'security:block:'.hash('sha256', '127.0.0.1');
        Cache::put($key, true, now()->addMinutes(10));

        $response = $this->intentar('clave-correcta');

        $response->assertStatus(429)
            ->assertJson(['message' => 'Acceso temporalmente restringido.']);

        $this->assertDatabaseHas('login_attempts', [
            'identifier' => 'usuario@seguridad.test',
            'ip_address' => '127.0.0.1',
            'status' => 'blocked',
        ]);

        $this->assertGuest();

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context = []) => $message === 'Intento desde IP bloqueada'
                && $context['ip'] === '127.0.0.1'
        );
    }

    public function test_acceso_tras_vencer_el_bloqueo(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->intentar('incorrecta-'.$i)->assertStatus(401);
        }

        $this->assertTrue(Cache::has('security:block:'.hash('sha256', '127.0.0.1')));

        $this->travel(11)->minutes();

        $response = $this->intentar('clave-correcta');

        $response->assertOk()
            ->assertJson(['message' => 'Autenticación correcta']);

        $this->assertAuthenticatedAs($this->user);
        $this->assertDatabaseHas('login_attempts', [
            'identifier' => 'usuario@seguridad.test',
            'status' => 'success',
        ]);
    }

    public function test_detecta_fallos_por_cuenta_desde_varias_ips(): void
    {
        // Tres IP distintas, un fallo cada una: ninguna alcanza el umbral por IP.
        $this->intentar('incorrecta-1', '10.0.0.11')->assertStatus(401);
        $this->intentar('incorrecta-2', '10.0.0.12')->assertStatus(401);
        $this->intentar('incorrecta-3', '10.0.0.13')->assertStatus(401);

        $this->assertSame(3, LoginAttempt::where('status', 'failed')->count());

        Log::shouldHaveReceived('alert')->withArgs(
            fn (string $message, array $context = []) => $message === 'Fallos repetidos sobre una misma cuenta'
                && $context['identifier'] === 'usuario@seguridad.test'
                && $context['failed_attempts'] === 3
        );

        Log::shouldNotHaveReceived('critical');
    }

    public function test_dashboard_requiere_autenticacion(): void
    {
        $this->get('/security-dashboard')->assertRedirect('/security-login');
    }

    public function test_dashboard_rechaza_usuario_sin_permiso(): void
    {
        $analista = User::factory()->create(['is_admin' => false]);

        $this->actingAs($analista)
            ->get('/security-dashboard')
            ->assertForbidden();

        $this->actingAs($analista)
            ->get('/security-dashboard/export')
            ->assertForbidden();
    }

    public function test_dashboard_muestra_metricas_ips_sospechosas_y_eventos(): void
    {
        foreach (range(1, 5) as $i) {
            LoginAttempt::create([
                'identifier' => 'objetivo@seguridad.test',
                'ip_address' => '192.0.2.10',
                'status' => 'failed',
                'user_agent' => 'PHPUnit',
                'created_at' => now()->subMinute(),
            ]);
        }

        LoginAttempt::create([
            'identifier' => 'usuario@seguridad.test',
            'ip_address' => '192.0.2.20',
            'status' => 'success',
            'user_agent' => 'PHPUnit',
            'created_at' => now(),
        ]);

        LoginAttempt::create([
            'identifier' => 'usuario@seguridad.test',
            'ip_address' => '192.0.2.30',
            'status' => 'blocked',
            'user_agent' => 'PHPUnit',
            'created_at' => now(),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/security-dashboard')
            ->assertOk()
            ->assertSee('Panel de seguridad')
            ->assertSee('192.0.2.10')
            ->assertSee('CRÍTICO')
            ->assertSee('objetivo@seguridad.test');
    }

    public function test_exporta_evidencias_en_csv(): void
    {
        LoginAttempt::create([
            'identifier' => 'usuario@seguridad.test',
            'ip_address' => '192.0.2.10',
            'status' => 'failed',
            'user_agent' => 'PHPUnit',
            'created_at' => now(),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get('/security-dashboard/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('ip_address', $csv);
        $this->assertStringContainsString('192.0.2.10', $csv);
    }
}
