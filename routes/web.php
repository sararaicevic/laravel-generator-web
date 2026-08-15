<?php

use App\Http\Controllers\GeneratedProjectController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/generator', [GeneratedProjectController::class, 'create'])->name('generator.create');
    Route::post('/generator', [GeneratedProjectController::class, 'store'])->name('generator.store');
    Route::get('/generator/{project}', [GeneratedProjectController::class, 'show'])->name('generator.show');
    Route::get('/generator/{project}/download', [GeneratedProjectController::class, 'download'])->name('generator.download');
});


require __DIR__.'/auth.php';
