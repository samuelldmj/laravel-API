<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\LikeController;
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


//likes route
// Route::middleware(['auth:sanctum'])->apiResource('likes', LikeController::class);
Route::post('posts/reaction', [LikeController::class, 'react'])->middleware('auth:sanctum');

Route::get('/posts/{post}/reaction', [LikeController::class, 'reactions'])->middleware('auth:sanctum');

//comment route
Route::middleware('auth:sanctum')->group(function () {
    // This route should come before the apiResource route to ensure it's matched first.
    // It's a specific route for an admin-only action.
    Route::get('comments', [CommentController::class, 'index'])->middleware('role:admin');

    // This is the standard apiResource route. It will automatically create routes for:
    // GET /comments (index), GET /comments/{comment} (show), POST /comments (store),
    // PUT/PATCH /comments/{comment} (update), and DELETE /comments/{comment} (destroy).
    // The middleware is applied to ALL of these routes.
    Route::apiResource('comments', CommentController::class);

    // This is a specific route for an admin-only action, so it has its own middleware.
    Route::patch('comments/{comment}/change-status', [CommentController::class, 'changeStatus'])->middleware('role:admin');
});



