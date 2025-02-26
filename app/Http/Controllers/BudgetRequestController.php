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
            ->paginate(10); // Added pagination

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

    public function reject($id)
    {
        $budgetRequest = BudgetRequest::findOrFail($id);
        $budgetRequest->status = 'rejected';
        $budgetRequest->reviewed_by = auth()->id();
        $budgetRequest->save();

        return response()->json([
            'message' => 'Budget request rejected successfully',
            'request' => $budgetRequest
        ]);
    }

    public function show(string $id)
    {
        $budgetRequest = BudgetRequest::with(['category', 'reviewer'])->findOrFail($id);

        return response()->json(['request' => $budgetRequest]);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'requested_amount' => 'sometimes|required|numeric|min:0',
            'description' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:pending,approved,rejected'
        ]);

        $budgetRequest = BudgetRequest::findOrFail($id);
        $budgetRequest->update($validated);

        return response()->json([
            'message' => 'Budget request updated successfully',
            'request' => $budgetRequest
        ]);
    }

    public function destroy(string $id)
    {
        $budgetRequest = BudgetRequest::findOrFail($id);
        $budgetRequest->delete();

        return response()->json([
            'message' => 'Budget request deleted successfully'
        ]);
    }

    public function getByStatus($user_id, $status)
    {
        $requests = BudgetRequest::where('user_id', $user_id)
            ->where('status', $status)
            ->with(['category', 'reviewer'])
            ->orderBy('request_date', 'desc')
            ->paginate(10); // Added pagination

        return response()->json(['requests' => $requests]);
    }
}
