<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gerencia extends Model
{
    protected $fillable = ['nombre'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class); // usuarios comunes de la gerencia
    }

    public function gerente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id'); // usuario gerente único
    }

    public function unidadAdministrativa(): BelongsTo
    {
        return $this->belongsTo(UnidadAdministrativa::class);
    }
}