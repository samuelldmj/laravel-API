<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PostController;
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
// Category Routes
Route::get('categories', [CategoryController::class, 'index']);

//you can see all categories but you cannot modify them
Route::middleware(['auth:sanctum', 'role:admin'])->apiResource('categories', CategoryController::class)->except(['index']);


// Post Routes
Route::get('posts', [PostController::class, 'index']);

//thumbnail delete
// Route::delete('posts/{post}/thumbnail', [PostController::class, 'deleteThumbnail'])->middleware('auth:sanctum', 'role:admin, author');

Route::middleware(['auth:sanctum', 'role:admin,author'])->apiResource('posts', PostController::class)->except('index');

// Alternative route for updating posts with multipart/form-data using POST
Route::post('posts/{post}/update', [PostController::class, 'update'])->middleware(['auth:sanctum', 'role:admin,author']);



