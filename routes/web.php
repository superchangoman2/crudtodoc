<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ExportarActividadesController;

Route::get('/exportar-pdf', [ExportarActividadesController::class, 'export'])
    ->name('actividades.exportar-pdf');
