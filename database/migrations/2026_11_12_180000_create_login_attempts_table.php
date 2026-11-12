<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de auditoría de intentos de autenticación.
     */
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->nullable();
            $table->string('ip_address', 45);
            $table->string('status', 20);
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Consulta principal: fallos por IP dentro de una ventana de tiempo.
            $table->index(['ip_address', 'status', 'created_at']);

            // Mejora: detección de fallos por cuenta desde varias IP.
            $table->index(['identifier', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
