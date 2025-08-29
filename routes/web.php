<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ExportarActividadesController;

Route::get('/exportar-pdf', [ExportarActividadesController::class, 'export'])
    ->name('actividades.exportar-pdf');


Route::get('/', function () {
    return Auth::check()
        ? redirect()->intended('/admin')
        : redirect('/admin/login');
});