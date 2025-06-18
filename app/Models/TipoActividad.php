<?php

namespace App\Models;

use App\Traits\TracksSoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoActividad extends Model
{
    use SoftDeletes, TracksSoftDeletes;

    protected $fillable = ['nombre'];
    protected $table = 'tipos_actividades';
}