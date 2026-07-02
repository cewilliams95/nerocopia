<?php

use App\Http\Controllers\RecipeController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('recipes.index');
    }

    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('recipes/import-url', [RecipeController::class, 'importUrl'])->name('recipes.import-url');
    Route::resource('recipes', RecipeController::class);
    Route::resource('tags', TagController::class)->only(['index', 'store', 'destroy']);
});

require __DIR__.'/settings.php';
