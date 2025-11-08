<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\ArticleController;
use App\Http\Controllers\Api\v1\UserController;

Route::post('/register', [UserController::class, 'store']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/tokens/create', function (Request $request) {
    $token = $request->user()->createToken($request->token_name);

    return ['token' => $token->plainTextToken];
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::get('/articles/{id}', [ArticleController::class, 'show']);
    Route::get('/user/preferences', [UserController::class, 'getPreferences']);
    Route::put('/user/preferences', [UserController::class, 'updatePreferences']);
});
