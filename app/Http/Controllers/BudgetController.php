<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
