<?php

use App\Http\Controllers\GlideController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Glide image optimization route - Use hash_name (filename) for cleaner URLs
Route::get('glide/{hashName}', GlideController::class)
    ->where('hashName', '[^/]+')
    ->name('glide');
