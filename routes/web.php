<?php

use App\Http\Controllers\SecurityDashboardController;
use App\Http\Controllers\SecurityLoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/security-login');
});

Route::get('/security-login', function () {
    return view('security-login');
})->middleware('guest')->name('login');

Route::post('/security-login', [SecurityLoginController::class, 'login'])
    ->middleware('guest');

Route::get('/security-dashboard', [SecurityDashboardController::class, 'index'])
    ->middleware(['auth', 'can:viewSecurityDashboard']);

Route::get('/security-dashboard/export', [SecurityDashboardController::class, 'export'])
    ->middleware(['auth', 'can:viewSecurityDashboard'])
    ->name('security.export');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/security-login');
})->middleware('auth')->name('logout');
