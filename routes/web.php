<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\YouTubeController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/google/callback', [YouTubeController::class, 'callback']);

Route::get('/youtube/connect', [YouTubeController::class, 'redirectToGoogle']);
Route::get('/google/callback', [YouTubeController::class, 'callback']);

Route::get('/youtube/upload', [YouTubeController::class, 'upload']);

