<?php

use App\Http\Controllers\GlideController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Glide image optimization route
Route::get('glide/{path}', GlideController::class)
    ->where('path', '.*')
    ->name('glide');
