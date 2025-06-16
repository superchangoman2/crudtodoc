<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExportarActividadesSimpleController;
use App\Http\Controllers\ExportarActividadesController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/actividades/exportar-pdf', [ExportarActividadesSimpleController::class, 'exportarPdf'])
    ->name('actividades.exportar-pdf-jerarquia');

Route::get('/exportar-pdf', [ExportarActividadesController::class, 'export'])
    ->name('actividades.exportar-pdf');
