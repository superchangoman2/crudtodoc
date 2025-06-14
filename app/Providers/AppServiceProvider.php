<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\Gerencia;
use App\Models\UnidadAdministrativa;
use App\Observers\UserObserver;
use App\Observers\GerenciaObserver;
use App\Observers\UnidadAdministrativaObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Gerencia::observe(GerenciaObserver::class);
        UnidadAdministrativa::observe(UnidadAdministrativaObserver::class);
    }
}
