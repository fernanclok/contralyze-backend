<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetRequestController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\RequisitionController;

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
    // Register a new user
    Route::post('/register', [AuthController::class, 'register']);
    // Login a user
    Route::post('/login', [AuthController::class, 'login']);
    // Logout a user
    Route::post('/logout', [AuthController::class, 'logout']);
    // Refresh token
    Route::post('/refresh', [AuthController::class, 'refresh']);
});

//user routes
Route::middleware('jwt')->prefix('users')->group(function () {
    // Create a new user
    Route::post('/create', [UserController::class, 'createUser']);
    // Get all users
    Route::get('/all', [UserController::class, 'allUsers']);
    // Get a update user
    Route::put('/update/{id}', [UserController::class, 'updateUser']);
});

//department routes
Route::middleware('jwt')->prefix('departments')->group(function () {
    // Create a new department
    Route::post('/create', [DepartmentController::class, 'createDepartment']);
    // Get all departments
    Route::get('/all', [DepartmentController::class, 'allDepartments']);
    // Route::get('/all/{id}', [DepartmentController::class, 'allDepartmentsByUser']);
    Route::delete('/delete/{id}', [DepartmentController::class, 'deleteDepartment']);
    Route::put('/update/{id}', [DepartmentController::class, 'updateDepartment']);
});

//client routes
Route::middleware('jwt')->prefix('clients')->group(function () {
    // Create a new client
    Route::post('/create', [ClientController::class, 'createClient']);
    // Get all clients
    Route::get('/all', [ClientController::class, 'allClients']);
    // Update a client
    Route::put('/client/update/{id}', [ClientController::class, 'updateClient']);
});

//supplier routes
Route::middleware('jwt')->prefix('suppliers')->group(function () {
    // Create a new supplier
    Route::post('/create', [SupplierController::class, 'createSupplier']);
    // Get all suppliers
    Route::get('/all', [SupplierController::class, 'allSuppliers']);
    // Update a supplier
    Route::put('/supplier/update/{id}', [SupplierController::class, 'updateSupplier']);
    // Delete a supplier
    Route::delete('/supplier/delete/{id}', [SupplierController::class, 'deleteSupplier']);
});

// Company routes
Route::middleware('jwt')->prefix('companies')->group(function () {
    // Create a new company
    Route::get('/company/{id}', [CompanyController::class, 'companyInfo']);
    // Get all users by company
    Route::get('/company/users/{id}', [CompanyController::class, 'allUsersByCompany']);
    // Get all departments by company
    Route::put('/company/update/{id}', [CompanyController::class, 'updateCompany']);
    // Delete a company
    // Route::delete('/company/delete/{id}', [CompanyController::class, 'deleteCompany']);
});

//category routes
Route::middleware('jwt')->prefix('categories')->group(function () {
    // Create a new category
    Route::post('/create', [CategoryController::class, 'createCategory']);
    // Get all categories
    Route::get('/all', [CategoryController::class, 'getCategories']);
    // Update a category
    Route::delete('/delete/{id}', [CategoryController::class, 'deleteCategory']);
    // Delete a category
    Route::put('/update/{id}', [CategoryController::class, 'updateCategory']);
});

// Budget Routes
Route::middleware('jwt')->prefix('budgets')->group(function () {
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
    // Get available budget
    Route::get('/available', [BudgetController::class, 'getAvailableBudget']);
});

// Budget Request Routes
Route::middleware('jwt')->prefix('budget-requests')->group(function () {
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
Route::middleware('jwt')->prefix('transactions')->group(function () {
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

// Requisition routes
Route::middleware('jwt')->prefix('requisitions')->group(function () {
    Route::get('/all', [RequisitionController::class, 'getRequisitions']);
    Route::get('/dashboard', [RequisitionController::class, 'requisitionDashboard']);
    Route::post('/create', [RequisitionController::class, 'createRequisition']);
    Route::put('/update/{id}', [RequisitionController::class, 'updateRequisition']);
    Route::put('/approve/{id}', [RequisitionController::class, 'approveRequisition']);
    Route::put('/reject/{id}', [RequisitionController::class, 'rejectRequisition']);
});
