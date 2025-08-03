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

#python
use App\Http\Controllers\PythonController;
Route::post('/python/dispatch/{script}', [PythonController::class,'dispatch']);
Route::get('/python/status/{uuid}',        [PythonController::class,'status']);

use App\Http\Controllers\PythonStreamController;
Route::prefix('python-stream')->group(function () {
    Route::get ('/history', [PythonStreamController::class, 'history']);
    Route::post('/start',   [PythonStreamController::class, 'start']);
    Route::post('/stop',    [PythonStreamController::class, 'stop']);
    Route::get ('/stream',  [PythonStreamController::class, 'stream']);
});