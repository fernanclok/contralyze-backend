<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetRequest;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Transaction;
use App\Traits\UsesPusher;

class BudgetController extends Controller
{
    use UsesPusher;

    public function index(Request $request)
    {
        $query = Budget::query()->with(['category', 'user']);

        // If a user_id is provided, filter by that user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $budgets = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['budgets' => $budgets]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Check if the user is an administrator
        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Only administrators can create budgets.'], 403);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'max_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'sometimes|in:active,inactive,expired'
        ]);

        // Assign the current user's ID
        $validated['user_id'] = $user->id;

        // Set default status to active
        if (!isset($validated['status'])) {
            $validated['status'] = 'active';
        }

        $budget = Budget::create($validated);

        // Use the trait method to send the event
        // Cambiar el canal a 'budgets' (público)
        $this->pushEvent('budgets', 'budget-created', [
            'message' => 'A new budget has been created',
            'budget' => $budget->load('category') // Asegúrate de cargar la relación 'category'
        ]);

        // Load the category relationship to return it in the response
        $budget->load('category');

        return response()->json([
            'message' => 'Budget created successfully',
            'budget' => $budget
        ], 201);
    }

    public function show($id)
    {
        $budget = Budget::with('category')->findOrFail($id);

        return response()->json(['budget' => $budget]);
    }

    public function edit($id, Request $request)
    {
        $budget = Budget::findOrFail($id);
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'max_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:active,inactive'
        ]);

        $validated['user_id'] = Auth::id();
        $budget->update($validated);

        return response()->json([
            'message' => 'Budget updated successfully',
            'budget' => $budget
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // Check if the user is an administrator
        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Only administrators can update budgets.'], 403);
        }

        $budget = Budget::findOrFail($id);
        $validated = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'max_amount' => 'sometimes|required|numeric|min:0',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'status' => 'sometimes|required|in:active,inactive,expired'
        ]);

        $budget->update($validated);
        $budget->load('category');

        // Cambiar el canal a 'budgets' (público)
        $this->pushEvent('budgets', 'budget-updated', [
            'message' => 'A budget has been updated',
            'budget' => $budget->load('category') // Asegúrate de cargar la relación 'category'
        ]);

        return response()->json([
            'message' => 'Budget updated successfully',
            'budget' => $budget
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();

        // Check if the user is an administrator
        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Only administrators can delete budgets.'], 403);
        }

        $budget = Budget::findOrFail($id);
        $budget->delete();

        // Cambiar el canal a 'budgets' (público)
        $this->pushEvent('budgets', 'budget-deleted', [
            'message' => 'A budget has been deleted',
            'id' => $id
        ]);

        return response()->json(['message' => 'Budget deleted successfully']);
    }

    public function search(Request $request)
    {
        $user_id = Auth::id();
        $query = Budget::where('user_id', $user_id);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('start_date')) {
            $query->where('start_date', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('end_date', '<=', $request->input('end_date'));
        }

        $budgets = $query->with('category')->get();

        return response()->json(['budgets' => $budgets]);
    }

    public function getStatistics()
    {
        $user = Auth::user();

        // Check if the user is an administrator
        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Only administrators can view budget statistics.'], 403);
        }

        $budgets = Budget::with('category')->get();

        $totalBudgets = $budgets->count();
        $totalActiveBudgets = $budgets->where('status', 'active')->count();
        $totalInactiveBudgets = $budgets->where('status', 'inactive')->count();
        $totalExpiredBudgets = $budgets->where('status', 'expired')->count();
        $totalBudgetAmount = $budgets->sum('max_amount');

        // Prepare data for the chart
        $chartData = $budgets->groupBy('category_id')->map(function ($categoryBudgets) {
            $categoryName = $categoryBudgets->first()->category->name;
            $totalBudgets = $categoryBudgets->count();
            $totalActiveBudgets = $categoryBudgets->where('status', 'active')->count();
            $totalInactiveBudgets = $categoryBudgets->where('status', 'inactive')->count();
            $totalExpiredBudgets = $categoryBudgets->where('status', 'expired')->count();
            $totalBudgetAmount = number_format($categoryBudgets->sum('max_amount'), 2);

            return [
                'name' => $categoryName,
                'total_budgets' => $totalBudgets,
                'total_active_budgets' => $totalActiveBudgets,
                'total_inactive_budgets' => $totalInactiveBudgets,
                'total_expired_budgets' => $totalExpiredBudgets,
                'total_budget_amount' => $totalBudgetAmount,
            ];
        })->values();

        return response()->json([
            'total_budgets' => $totalBudgets,
            'total_active_budgets' => $totalActiveBudgets,
            'total_inactive_budgets' => $totalInactiveBudgets + $totalExpiredBudgets,
            'total_budget_amount' => $totalBudgetAmount,
            'chart_data' => $chartData,
        ]);
    }

    public function getEmergencyFund()
    {
        $user = Auth::user();

        // Check if the user is an administrator
        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Only administrators can view budget statistics.'], 403);
        }

        $budgets = Budget::get();
        $transactions = Transaction::get();
        $expenses = $transactions->where('type', 'expense')
            ->where('status', 'completed');

        // Get budgets with active status
        $active_budgets = $budgets->where('status', 'active');

        // Calculate current values
        $EmergencyFund = round($active_budgets->sum('max_amount') * 0.1, 2);
        $TotalBudgetAmount = round($budgets->sum('max_amount'), 2);
        $TotalExpenses = round($expenses->sum('amount'), 2);
        $LastUpdate = now()->format('Y-m-d');

        // Get previous values from cache
        $previousData = Cache::get('emergency_fund_data', [
            'emergency_fund' => null,
            'total_budget_amount' => null,
            'total_expenses' => null,
        ]);

        // Compare current values with previous ones
        $changes = [
            'emergency_fund' => $this->compareValues($previousData['emergency_fund'], $EmergencyFund),
            'total_budget_amount' => $this->compareValues($previousData['total_budget_amount'], $TotalBudgetAmount),
            'total_expenses' => $this->compareValues($previousData['total_expenses'], $TotalExpenses),
        ];

        // Save current values to cache
        Cache::put('emergency_fund_data', [
            'emergency_fund' => $EmergencyFund,
            'total_budget_amount' => $TotalBudgetAmount,
            'total_expenses' => $TotalExpenses,
        ], now()->addHours(1));

        return response()->json([
            'emergency_fund' => $EmergencyFund,
            'total_budget_amount' => $TotalBudgetAmount,
            'total_expenses' => $TotalExpenses,
            'target_date' => $LastUpdate,
            'changes' => $changes,
        ]);
    }

    private function compareValues($previous, $current)
    {
        $previous = is_numeric($previous) ? (float) $previous : null;
        $current = is_numeric($current) ? (float) $current : null;

        $lastStatus = Cache::get('last_status', null);

        if ($previous === null) {
            return [
                'status' => 'new',
                'percentage' => null,
            ];
        }

        if ($previous == 0) {
            return [
                'status' => 'new',
                'percentage' => 0,
            ];
        }

        if ($current > $previous) {
            $percentageChange = (($current - $previous) / $previous) * 100;
            Cache::put('last_status', 'increased', now()->addHours(3));
            return [
                'status' => 'increased',
                'previous_status' => $lastStatus,
                'percentage' => number_format($percentageChange, 2),
            ];
        } elseif ($current < $previous) {
            $percentageChange = (($previous - $current) / $previous) * 100;
            Cache::put('last_status', 'decreased', now()->addHours(3));
            return [
                'status' => 'decreased',
                'previous_status' => $lastStatus,
                'percentage' => number_format($percentageChange, 2),
            ];
        } else {
            return [
                'status' => 'error',
                'previous_status' => $lastStatus,
                'percentage' => 0,
            ];
        }
    }

    public function getByCategory($category_id)
    {
        $budgets = Budget::where('category_id', $category_id)
            ->with(['category', 'user'])
            ->get();

        return response()->json(['budgets' => $budgets]);
    }

    public function getAvailableBudget(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'department_id' => 'sometimes|exists:departments,id'
        ]);

        $categoryId = $validated['category_id'];

        $totalBudget = Budget::where('status', 'active')
            ->where('category_id', $categoryId)
            ->sum('max_amount');

        $totalApproved = BudgetRequest::where('status', 'approved')
            ->where('category_id', $categoryId)
            ->sum('requested_amount');

        $availableBudget = $totalBudget - $totalApproved;

        $response = [
            'category_id' => $categoryId,
            'total_budget' => $totalBudget,
            'total_approved' => $totalApproved,
            'available_budget' => $availableBudget
        ];

        if (isset($validated['department_id'])) {
            $departmentId = $validated['department_id'];

            $departmentUsers = User::where('department_id', $departmentId)->pluck('id');

            $departmentBudget = Budget::whereIn('user_id', $departmentUsers)
                ->where('status', 'active')
                ->where('category_id', $categoryId)
                ->sum('max_amount');

            $departmentApproved = BudgetRequest::whereIn('user_id', $departmentUsers)
                ->where('status', 'approved')
                ->where('category_id', $categoryId)
                ->sum('requested_amount');

            $departmentAvailable = $departmentBudget - $departmentApproved;

            $department = Department::find($departmentId);

            $response['department'] = [
                'id' => $departmentId,
                'name' => $department ? $department->name : 'Department not found',
                'budget' => $departmentBudget,
                'approved' => $departmentApproved,
                'available' => $departmentAvailable
            ];
        }

        return response()->json($response);
    }
}
