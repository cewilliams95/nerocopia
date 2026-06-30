<?php

use App\Http\Controllers\RecipeController;
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
});

require __DIR__.'/settings.php';
