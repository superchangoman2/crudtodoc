<?php

namespace App\Models;

use App\Traits\TracksSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gerencia extends Model
{
    use SoftDeletes, TracksSoftDeletes;
    protected $fillable = ['nombre', 'unidad_administrativa_id'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'pertenece_id');
    }

    public function gerente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function unidadAdministrativa(): BelongsTo
    {
        return $this->belongsTo(UnidadAdministrativa::class);
    }
}