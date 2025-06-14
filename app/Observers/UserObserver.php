<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Gerencia;
use App\Models\UnidadAdministrativa;

class UserObserver
{
    public function updated(User $user)
    {
        if ($user->isDirty('pertenece_id')) {
            $originalId = $user->getOriginal('pertenece_id');
            $nuevoId = $user->pertenece_id;

            // Si ya tenian un ID de pertenencia, lo limpio
            if ($user->hasRole('administrador-unidad') && $originalId) {
                UnidadAdministrativa::where('id', $originalId)
                    ->where('administrador_id', $user->id)
                    ->update(['administrador_id' => null]);
            }
            if ($user->hasRole('gerente') && $originalId) {
                Gerencia::where('id', $originalId)
                    ->where('gerente_id', $user->id)
                    ->update(['gerente_id' => null]);
            }

            if ($user->hasRole('subgerente') && $originalId) {
                Gerencia::where('id', $originalId)
                    ->where('subgerente_id', $user->id)
                    ->update(['subgerente_id' => null]);
            }
        }

        // Ahora asigno el nuevo ID de pertenencia
        if ($user->hasRole('administrador-unidad') && $user->pertenece_id) {
            UnidadAdministrativa::where('id', $user->pertenece_id)
                ->update(['administrador_id' => $user->id]);
        }
        if ($user->hasRole('gerente') && $user->pertenece_id) {
            Gerencia::where('id', $user->pertenece_id)
                ->update(['gerente_id' => $user->id]);
        }
        if ($user->hasRole('subgerente') && $user->pertenece_id) {
            Gerencia::where('id', $user->pertenece_id)
                ->update(['subgerente_id' => $user->id]);
        }
    }
    public function deleted(User $user): void
    {
        // Elimino las relaciones de pertenencia al borrar
        UnidadAdministrativa::where('administrador_id', $user->id)
            ->update(['administrador_id' => null]);
        Gerencia::where('gerente_id', $user->id)
            ->update(['gerente_id' => null]);
        Gerencia::where('subgerente_id', $user->id)
            ->update(['subgerente_id' => null]);
        // Si se usan soft deletes, se puede usar el siguiente código
        if (method_exists($user, 'trashed') && $user->trashed()) {
            if ($user->hasRole('administrador-unidad')) {
                UnidadAdministrativa::where('administrador_id', $user->id)
                    ->update(['deleted_by' => $user->id]);
            }

            if ($user->hasRole('gerente')) {
                Gerencia::where('gerente_id', $user->id)
                    ->update(['deleted_by' => $user->id]);
            }

            if ($user->hasRole('subgerente')) {
                Gerencia::where('subgerente_id', $user->id)
                    ->update(['deleted_by' => $user->id]);
            }
        }
    }

    public function created(User $user): void
    {
    }
    public function restored(User $user): void
    {
    }
    public function forceDeleted(User $user): void
    {
    }
}
