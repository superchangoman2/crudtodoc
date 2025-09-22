<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ExportarActividadesController;

Route::get('/exportar-pdf', [ExportarActividadesController::class, 'exportPdf'])
    ->name('actividades.exportar-pdf');

Route::get('/exportar-doc', [ExportarActividadesController::class, 'exportDoc'])
    ->name('actividades.exportar-doc');

Route::get('/', function () {
    return Auth::check()
        ? redirect()->intended('/admin')
        : redirect('/admin/login');
});