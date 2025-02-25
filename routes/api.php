<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\AuthController;
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

//client routes
Route::prefix('clients')->group(function () {
    Route::post('/create', [ClientController::class, 'createClient']);
    Route::get('/all', [ClientController::class, 'allClients']);
});

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetRequestController;

// Budget Routes
Route::prefix('budgets')->group(function () {
    // List all budgets for a user
    Route::get('/user/{user_id}', [BudgetController::class, 'index']);
    
    // Get a specific budget
    Route::get('/{id}', [BudgetController::class, 'show']);
    
    // Create a new budget
    Route::post('/create', [BudgetController::class, 'store']);
    
    // Update a budget
    Route::put('/{id}', [BudgetController::class, 'update']);
    
    // Delete a budget
    Route::delete('/{id}', [BudgetController::class, 'destroy']);
    
    // Get budget statistics
    Route::get('/statistics/{user_id}', [BudgetController::class, 'getStatistics']);
    
    // Get budgets by category
    Route::get('/category/{category_id}', [BudgetController::class, 'getByCategory']);
});

// Budget Request Routes
Route::prefix('budget-requests')->group(function () {
    // List all budget requests for a user
    Route::get('/user/{user_id}', [BudgetRequestController::class, 'index']);
    
    // Get a specific budget request
    Route::get('/{id}', [BudgetRequestController::class, 'show']);
    
    // Create a new budget request
    Route::post('/create', [BudgetRequestController::class, 'store']);
    
    // Update a budget request
    Route::put('/{id}', [BudgetRequestController::class, 'update']);
    
    // Delete a budget request
    Route::delete('/{id}', [BudgetRequestController::class, 'destroy']);
    
    // Approve a budget request
    Route::put('/{id}/approve', [BudgetRequestController::class, 'approve']);
    
    // Reject a budget request
    Route::put('/{id}/reject', [BudgetRequestController::class, 'reject']);
    
    // Get pending requests
    Route::get('/pending/{user_id}', [BudgetRequestController::class, 'getPendingRequests']);
});
