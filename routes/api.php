<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});


//Blog Categories Route endpoints
// Publicly accessible routes
Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{category}', [CategoryController::class, 'show']);
Route::middleware('auth:sanctum')->apiResource('categories', CategoryController::class)->except(['index', 'show']);


//post routes
Route::get('posts', [App\Http\Controllers\Api\PostController::class, 'index']);
Route::patch('posts/{post}', [App\Http\Controllers\Api\PostController::class, 'show']);
Route::middleware('auth:sanctum')->apiResource('posts', App\Http\Controllers\Api\PostController::class)->except(['index', 'show']);