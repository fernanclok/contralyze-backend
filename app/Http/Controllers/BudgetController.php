<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    public function index()
    {
        $user_id = Auth::id();
        $budgets = Budget::where('user_id', $user_id)
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['budgets' => $budgets]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'max_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:active,inactive'
        ]);

        $validated['user_id'] = Auth::id();
        $budget = Budget::create($validated);

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

    public function destroy($id)
    {
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
