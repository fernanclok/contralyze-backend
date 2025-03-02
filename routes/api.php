<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetRequestController;
use App\Http\Controllers\TransactionController;

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
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
});

//user routes
Route::middleware('jwt')->prefix('users')->group(function () {
    Route::post('/create', [UserController::class, 'createUser']);
    Route::get('/all', [UserController::class, 'allUsers']);
    Route::put('/update/{id}', [UserController::class, 'updateUser']);
});

//department routes
Route::middleware('jwt')->prefix('departments')->group(function () {
    Route::post('/create', [DepartmentController::class, 'createDepartment']);
    Route::get('/all', [DepartmentController::class, 'allDepartments']);

    //  get company departments
    Route::get('/all/{id}', [DepartmentController::class, 'allDepartmentsByUser']);

    Route::delete('/delete/{id}', [DepartmentController::class, 'deleteDepartment']);
    Route::put('/update/{id}', [DepartmentController::class, 'updateDepartment']);
});

//client routes
Route::middleware('jwt')->prefix('clients')->group(function () {
    Route::post('/create', [ClientController::class, 'createClient']);
    Route::get('/all', [ClientController::class, 'allClients']);
    Route::get('/all/{id}', [ClientController::class, 'allClientsbyUser']);
    Route::put('/client/update/{id}', [ClientController::class, 'updateClient']);
    Route::delete('/client/delete/{id}', [ClientController::class, 'deleteClient']);
});


// Company routes
Route::prefix('companies')->group(function () {
    Route::get('/all', [CompanyController::class, 'allCompanies']);
    Route::get('/company/{id}', [CompanyController::class, 'companyInfo']);
    Route::get('/company/users/{id}', [CompanyController::class, 'allUsersByCompany']);
    Route::put('/company/update/{id}', [CompanyController::class, 'updateCompany']);
    Route::delete('/company/delete/{id}', [CompanyController::class, 'deleteCompany']);
});

//category routes
Route::middleware('jwt')->prefix('categories')->group(function () {
    Route::post('/create', [CategoryController::class, 'createCategory']);
    Route::get('/all', [CategoryController::class, 'getCategories']);
    Route::delete('/delete/{id}', [CategoryController::class, 'deleteCategory']);
    Route::put('/update/{id}', [CategoryController::class, 'updateCategory']);
});

// Budget Routes
Route::prefix('budgets')->group(function () {
    // List all budgets for a specific user
    Route::get('/all', [BudgetController::class, 'index']);
    
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
    // List all budget requests
    Route::get('/all', [BudgetRequestController::class, 'index']);
    
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

// Transaction Routes
Route::prefix('transactions')->group(function () {
    // List all transactions
    Route::get('/all', [TransactionController::class, 'index']);
    
    // Get a specific transaction
    Route::get('/{id}', [TransactionController::class, 'show']);
    
    // Create a new transaction
    Route::post('/create', [TransactionController::class, 'store']);
    
    // Update a transaction
    Route::put('/{id}', [TransactionController::class, 'update']);
    
    // Delete a transaction
    Route::delete('/{id}', [TransactionController::class, 'destroy']);
    
    // Get transactions by category
    Route::get('/category/{category_id}', [TransactionController::class, 'getByCategory']);
});
