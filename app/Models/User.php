<?php

namespace App\Models;

use App\Traits\TracksSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes, TracksSoftDeletes;


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['first_name', 'last_name', 'email', 'password'];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function actividades(): HasMany
    {
        return $this->hasMany(Actividad::class);
    }

    public function gerencia(): BelongsTo
    {
        return $this->belongsTo(Gerencia::class, 'pertenece_id');
    }

    public function unidadAdministrativa(): BelongsTo
    {
        return $this->belongsTo(UnidadAdministrativa::class, 'pertenece_id');
    }


    public function getNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getPertenenciaInfo(): array
    {
        if ($this->hasRole('administrador-unidad')) {
            return [
                'tipo' => Actividad::TIPO_UNIDAD,
                'nombre' => $this->unidadAdministrativa?->nombre ?? 'Sin asignar',
            ];
        }

        if ($this->hasAnyRole(['admin', 'gerente', 'subgerente', 'usuario'])) {
            return [
                'tipo' => Actividad::TIPO_GERENCIA,
                'nombre' => $this->gerencia?->nombre ?? 'Sin asignar',
            ];
        }

        return [
            'tipo' => Actividad::TIPO_OTRO,
            'nombre' => 'Sin asignar',
        ];
    }

}
