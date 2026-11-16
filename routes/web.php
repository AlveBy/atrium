<?php

declare(strict_types=1);

use App\Http\Controllers\ArticleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Every route in here also answers under a language prefix (/de/articles/…)
// for each content language added in the admin.
Route::localized(function () {
    Route::get('articles/{code}', [ArticleController::class, 'show'])->name('articles.show');
});
