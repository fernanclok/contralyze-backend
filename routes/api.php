<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//auth routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::get('/refresh', [AuthController::class, 'refresh']);
});

Route::get('/me', [AuthController::class, 'me'])->middleware('jwt.auth');

//user routes
Route::prefix('users')->group(function () {
    Route::post('/create', [UserController::class, 'createUser']);
    Route::get('/all', [UserController::class, 'allUsers']);
})->middleware('jwt.auth');

//client routes
Route::prefix('clients')->group(function () {
    Route::post('/create', [ClientController::class, 'createClient']);
    Route::get('/all', [ClientController::class, 'allClients']);
});
