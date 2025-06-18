<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait TracksCreatedRole
{
    public static function bootTracksCreatedRole()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by_role = Auth::user()->getRoleNames()->first(); // Spatie
            }
        });
    }
}

// Necesita en la migración
// $table->timestamps();
// $table->string('created_by_role')->nullable();