<?php

namespace App\Observers;

use App\Models\Gerencia;
use App\Models\User;

class GerenciaObserver
{
    public function updated(Gerencia $gerencia)
    {
        if ($gerencia->isDirty('gerente_id')) {
            User::where('id', $gerencia->getOriginal('gerente_id'))
                ->where('pertenece_id', $gerencia->id)
                ->update(['pertenece_id' => null]);

            if ($gerencia->gerente_id) {
                User::where('id', $gerencia->gerente_id)
                    ->update(['pertenece_id' => $gerencia->id]);
            }
        }

        if ($gerencia->isDirty('subgerente_id')) {
            User::where('id', $gerencia->getOriginal('subgerente_id'))
                ->where('pertenece_id', $gerencia->id)
                ->update(['pertenece_id' => null]);

            if ($gerencia->subgerente_id) {
                User::where('id', $gerencia->subgerente_id)
                    ->update(['pertenece_id' => $gerencia->id]);
            }
        }
    }
    public function deleting(Gerencia $gerencia)
    {
        $usuarios = User::where('pertenece_id', $gerencia->id)->count();

        if ($usuarios > 0) {
            throw new \Exception('No se puede eliminar una gerencia que tiene usuarios asignados.');
        }

        // Si es soft delete, limpia los campos en usuarios
        User::where('pertenece_id', $gerencia->id)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['gerente', 'subgerente']))
            ->update(['pertenece_id' => null]);

        $gerencia->gerente_id = null;
        $gerencia->subgerente_id = null;
    }

    public function created(Gerencia $gerencia): void
    {
    }
    public function restored(Gerencia $gerencia): void
    {
    }
    public function forceDeleted(Gerencia $gerencia): void
    {
    }
}
