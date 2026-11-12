<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    /**
     * El registro de auditoría solo guarda la fecha de creación.
     */
    public $timestamps = false;

    protected $fillable = [
        'identifier',
        'ip_address',
        'status',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
