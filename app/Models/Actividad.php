<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Gerencia;
use App\Models\TipoActividad;

class Actividad extends Model
{
    public const TIPO_UNIDAD = 'Unidad Administrativa';
    public const TIPO_GERENCIA = 'Gerencia';
    public const TIPO_OTRO = 'Sin asignar';

    use SoftDeletes;
    protected $fillable = [
        'titulo',
        'descripcion',
        'fecha',
        'user_id',
        'tipo_actividad_id',
        'pertenencia_nombre',
        'pertenencia_tipo',
    ];

    protected $table = 'actividades';
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    protected static function booted()
    {
        static::deleting(function ($actividad) {
            if (auth()->check() && !$actividad->isForceDeleting()) {
                $actividad->deleted_by = auth()->id();
                $actividad->saveQuietly();
            }
        });
    }

    public function eliminadoPor()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
