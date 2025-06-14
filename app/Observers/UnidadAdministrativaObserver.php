<?php
namespace App\Observers;

use App\Models\User;
use App\Models\UnidadAdministrativa;

class UnidadAdministrativaObserver
{
    public function updated(UnidadAdministrativa $unidad)
    {
        if ($unidad->isDirty('administrador_id')) {
            User::where('id', $unidad->getOriginal('administrador_id'))
                ->where('pertenece_id', $unidad->id)
                ->update(['pertenece_id' => null]);

            if ($unidad->administrador_id) {
                User::where('id', $unidad->administrador_id)
                    ->update(['pertenece_id' => $unidad->id]);
            }
        }
    }

    public function deleting(UnidadAdministrativa $unidad)
    {
        $usuarios = User::where('pertenece_id', $unidad->id)->count();

        if ($usuarios > 0) {
            throw new \Exception('No se puede eliminar una unidad administrativa que tiene usuarios asignados.');
        }

        User::where('pertenece_id', $unidad->id)
            ->whereHas('roles', fn($q) => $q->where('name', 'administrador-unidad'))
            ->update(['pertenece_id' => null]);

        $unidad->administrador_id = null;
    }

    public function created(UnidadAdministrativa $unidad): void
    {
    }
    public function restored(UnidadAdministrativa $unidad): void
    {
    }
    public function forceDeleted(UnidadAdministrativa $unidad): void
    {
    }
}
