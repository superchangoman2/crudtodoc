<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait TracksSoftDeletes
{
    public static function bootTracksSoftDeletes()
    {
        static::deleting(function ($model) {
            if (Auth::check() && !$model->isForceDeleting()) {
                $model->deleted_by = Auth::id();
                $model->saveQuietly();
            }
        });

        static::restored(function ($model) {
            $model->deleted_by = null;
            $model->saveQuietly();
        });
    }

    public function eliminadoPor()
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }
}