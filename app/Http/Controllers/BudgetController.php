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

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $query = Budget::query()->with(['category', 'user']);

        // Si se proporciona un user_id, filtrar por ese usuario
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $budgets = $query->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['budgets' => $budgets]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Verificar si el usuario es administrador
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

        // Asignar el ID del usuario actual
        $validated['user_id'] = $user->id;

        // Establecer estado activo por defecto
        if (!isset($validated['status'])) {
            $validated['status'] = 'active';
        }

        $budget = Budget::create($validated);

        // Cargar la relación category para devolverla en la respuesta
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

        // Verificar si el usuario es administrador
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

        return response()->json([
            'message' => 'Budget updated successfully',
            'budget' => $budget
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();

        // Verificar si el usuario es administrador
        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Only administrators can delete budgets.'], 403);
        }

        $budget = Budget::findOrFail($id);
        $budget->delete();

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

        // Check if the user is an admin
        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Only administrators can view budget statistics.'], 403);
        }

        $budgets = Budget::with('category')->get();

        $totalBudgets = $budgets->count();
        $totalActiveBudgets = $budgets->where('status', 'active')->count();
        $totalInactiveBudgets = $budgets->where('status', 'inactive')->count();
        $totalExpiredBudgets = $budgets->where('status', 'expired')->count();
        $totalBudgetAmount = $budgets->sum('max_amount');


        // Preparar datos para la gráfica
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

        // Check if the user is an admin
        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Only administrators can view budget statistics.'], 403);
        }

        $budgets = Budget::get();
        $transactions = Transaction::get();
        $expenses = $transactions->where('type', 'expense')
            ->where('status', 'completed');

        // obtener las budgets que tengan como status active
        $active_budgets = $budgets->where('status', 'active');

        // Calcular los valores actuales
        $EmergencyFund = round($active_budgets->sum('max_amount') * 0.1, 2); // Asegurarse de que sea numérico
        $TotalBudgetAmount = round($budgets->sum('max_amount'), 2); // Asegurarse de que sea numérico
        $TotalExpenses = round($expenses->sum('amount'), 2); // Placeholder para gastos, asegurarse de que sea numérico
        $LastUpdate = now()->format('Y-m-d');

        // Obtener los valores anteriores de la caché
        $previousData = Cache::get('emergency_fund_data', [
            'emergency_fund' => null,
            'total_budget_amount' => null,
            'total_expenses' => null,
        ]);

        // Comparar los valores actuales con los anteriores
        $changes = [
            'emergency_fund' => $this->compareValues($previousData['emergency_fund'], $EmergencyFund),
            'total_budget_amount' => $this->compareValues($previousData['total_budget_amount'], $TotalBudgetAmount),
            'total_expenses' => $this->compareValues($previousData['total_expenses'], $TotalExpenses),
        ];

        // Guardar los valores actuales en la caché
        Cache::put('emergency_fund_data', [
            'emergency_fund' => $EmergencyFund,
            'total_budget_amount' => $TotalBudgetAmount,
            'total_expenses' => $TotalExpenses,
        ], now()->addHours(1)); // La caché expira en 1 hora

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
        // Convertir valores a numéricos si no lo son
        $previous = is_numeric($previous) ? (float) $previous : null;
        $current = is_numeric($current) ? (float) $current : null;

        // Obtener el último estado de la caché
        $lastStatus = Cache::get('last_status', null);

        if ($previous === null) {
            return [
                'status' => 'new', // No hay datos anteriores
                'percentage' => null,
            ];
        }

        if ($previous == 0) {
            return [
                'status' => 'new', // División por cero
                'percentage' => 0,
            ];
        }

        if ($current > $previous) {
            $percentageChange = (($current - $previous) / $previous) * 100;
            Cache::put('last_status', 'increased', now()->addHours(3)); // Guardar el estado en la caché
            return [
                'status' => 'increased',
                'previous_status' => $lastStatus, // Devolver el último estado
                'percentage' => number_format($percentageChange, 2),
            ];
        } elseif ($current < $previous) {
            $percentageChange = (($previous - $current) / $previous) * 100;
            Cache::put('last_status', 'decreased', now()->addHours(3)); // Guardar el estado en la caché
            return [
                'status' => 'decreased',
                'previous_status' => $lastStatus, // Devolver el último estado
                'percentage' => number_format($percentageChange, 2),
            ];
        } else {
            return [
                'status' => 'error', // Caso inesperado
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

    /**
     * Obtener el presupuesto disponible para una categoría específica
     */
    public function getAvailableBudget(Request $request)
    {       
        // Validar los parámetros
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'department_id' => 'sometimes|exists:departments,id'
        ]);

        $categoryId = $validated['category_id'];

        // Obtener presupuesto total para la categoría
        $totalBudget = Budget::where('status', 'active')
            ->where('category_id', $categoryId)
            ->sum('max_amount');

        // Obtener total aprobado para la categoría
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

        // Si se solicita información de un departamento específico
        if (isset($validated['department_id'])) {
            $departmentId = $validated['department_id'];

            // Obtener usuarios del departamento
            $departmentUsers = User::where('department_id', $departmentId)->pluck('id');

            // Presupuesto asignado al departamento
            $departmentBudget = Budget::whereIn('user_id', $departmentUsers)
                ->where('status', 'active')
                ->where('category_id', $categoryId)
                ->sum('max_amount');

            // Presupuesto ya aprobado para el departamento
            $departmentApproved = BudgetRequest::whereIn('user_id', $departmentUsers)
                ->where('status', 'approved')
                ->where('category_id', $categoryId)
                ->sum('requested_amount');

            $departmentAvailable = $departmentBudget - $departmentApproved;

            // Obtener información del departamento
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
