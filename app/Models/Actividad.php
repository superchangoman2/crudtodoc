<?php

namespace App\Models;


use App\Traits\TracksSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Gerencia;
use App\Models\TipoActividad;

class Actividad extends Model
{
    public const TIPO_UNIDAD = 'Unidad Administrativa';
    public const TIPO_GERENCIA = 'Gerencia';
    public const TIPO_OTRO = 'Sin asignar';

    use SoftDeletes, TracksSoftDeletes;
    protected $fillable = [
        'titulo',
        'descripcion',
        'fecha',
        'user_id',
        'tipo_actividad_id',
        'pertenencia_nombre',
        'pertenencia_tipo',
        'autorizado',
    ];

    protected $table = 'actividades';
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function gerencia(): BelongsTo
    {
        return $this->belongsTo(Gerencia::class, 'pertenece_id');
    }

    public function getGerenciaAsignadaAttribute(): ?Gerencia
    {
        return $this->hasAnyRole(['admin', 'gerente', 'subgerente', 'usuario'])
            ? $this->gerencia
            : null;
    }

    public function tipoActividad(): BelongsTo
    {
        return $this->belongsTo(TipoActividad::class);
    }
}
