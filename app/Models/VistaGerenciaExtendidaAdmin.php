<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VistaGerenciaExtendidaAdmin extends Model
{
    protected $table = 'vista_gerencias_extendida_admin';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    public function getKeyName(): string
    {
        return 'id';
    }

    public function save(array $options = [])
    {
        throw new \Exception("Vista de solo lectura.");
    }
}
