<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait TracksUser
{
    public static function bootTracksUser()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    public function creadoPor()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function modificadoPor()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}

// Necesita en la migración
// $table->timestamps();
// $table->foreignId('created_by')->nullable()->constrained('users');
// $table->foreignId('updated_by')->nullable()->constrained('users');