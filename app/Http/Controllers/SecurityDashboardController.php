<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use Illuminate\Support\Facades\DB;

class SecurityDashboardController extends Controller
{
    /**
     * Panel de monitoreo con contadores, IPs sospechosas y últimos eventos.
     */
    public function index()
    {
        $since = now()->subHours(24);

        $stats = [];
        foreach (['success', 'failed', 'blocked'] as $status) {
            $stats[$status] = LoginAttempt::where('status', $status)
                ->where('created_at', '>=', $since)
                ->count();
        }

        $suspiciousIps = LoginAttempt::select(
            'ip_address',
            DB::raw('COUNT(*) as total')
        )
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->groupBy('ip_address')
            ->havingRaw('COUNT(*) >= 3')
            ->orderByDesc('total')
            ->get();

        $blockedAttempts = LoginAttempt::where('status', 'blocked')
            ->where('created_at', '>=', now()->subMinutes(10))
            ->orderByDesc('created_at')
            ->get();

        $recentAttempts = LoginAttempt::orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('security-dashboard', compact(
            'stats',
            'suspiciousIps',
            'blockedAttempts',
            'recentAttempts'
        ));
    }

    /**
     * Mejora: exportación de evidencias en CSV para el análisis posterior.
     */
    public function export()
    {
        $attempts = LoginAttempt::orderByDesc('created_at')->limit(1000)->get();

        $filename = 'login-attempts-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($attempts) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'id',
                'identifier',
                'ip_address',
                'status',
                'user_agent',
                'created_at',
            ]);

            foreach ($attempts as $attempt) {
                fputcsv($output, [
                    $attempt->id,
                    $attempt->identifier,
                    $attempt->ip_address,
                    $attempt->status,
                    $attempt->user_agent,
                    optional($attempt->created_at)->toDateTimeString(),
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
