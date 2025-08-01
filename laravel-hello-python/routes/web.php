<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

#python controllers

use App\Http\Controllers\PythonController;
Route::get('hello-python', [PythonController::class, 'handle']);
Route::get('plot-sine', [PythonController::class, 'plotSine']);

use App\Http\Controllers\PythonStreamController;
Route::get('/python-stream/history', [PythonStreamController::class,'history']);
Route::post('/python-stream/start',    [PythonStreamController::class,'start']);
Route::post('/python-stream/stop',     [PythonStreamController::class,'stop']);
Route::get('/python-stream/stream',    [PythonStreamController::class,'stream']);