<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActividadExportController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::redirect('/', '/admin');
Route::get('/actividades/exportar', [ActividadExportController::class, 'exportarPdf']);