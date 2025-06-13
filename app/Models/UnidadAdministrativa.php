<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadAdministrativa extends Model
{
    protected $fillable = ['nombre'];
    protected $table = 'unidades_administrativas';


    public function gerencias(): HasMany
    {
        return $this->hasMany(Gerencia::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
