<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoActividad extends Model
{
    protected $fillable = ['nombre'];
    protected $table = 'tipos_actividades';
}