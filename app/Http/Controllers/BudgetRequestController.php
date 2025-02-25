<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BudgetRequest;

class BudgetRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($user_id)
    {
        $requests = BudgetRequest::where('user_id', $user_id)
            ->with(['category', 'reviewer'])
            ->orderBy('request_date', 'desc')
            ->get();

        return response()->json(['requests' => $requests]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            'requested_amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'status' => 'required|in:pending,approved,rejected'
        ]);

        $budgetRequest = BudgetRequest::create($validated);

        return response()->json([
            'message' => 'Budget request created successfully',
            'request' => $budgetRequest
        ], 201);
    }

    public function approve($id)
    {
        $budgetRequest = BudgetRequest::findOrFail($id);
        $budgetRequest->status = 'approved';
        $budgetRequest->reviewed_by = auth()->id();
        $budgetRequest->save();

        return response()->json([
            'message' => 'Budget request approved successfully',
            'request' => $budgetRequest
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
