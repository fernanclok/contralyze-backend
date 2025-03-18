<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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
        // Calcular los valores actuales
        $EmergencyFund = number_format($budgets->sum('max_amount') * 0.1, 2);
        $TotalBudgetAmount = number_format($budgets->sum('max_amount'), 2);
        $TotalExpenses = number_format(0, 2);
        $LastUpdate = now()->format('Y-m-d');

        // Obtener los valores anteriores de la cache
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
        if ($previous === null) {
            return 'new'; // No hay datos anteriores
        }

        if ($current > $previous) {
            return 'increased';
        } elseif ($current < $previous) {
            return 'decreased';
        } else {
            return 'unchanged';
        }
    }
}
