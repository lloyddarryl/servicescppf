<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Route pour le cookie CSRF de Sanctum
Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// IMPORTANT : Ajoutez cette ligne pour les cookies CSRF
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])
    ->middleware('web');

// Si vous voulez servir votre frontend React depuis Laravel
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');