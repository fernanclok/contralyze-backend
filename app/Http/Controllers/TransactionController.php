<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Transaction::query()
                ->with(['category', 'user', 'supplier', 'client', 'invoices']);
            
            // Apply filters if provided
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }
            
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }
            
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            
            if ($request->has('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }
            
            if ($request->has('client_id')) {
                $query->where('client_id', $request->client_id);
            }
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('from_date') && $request->has('to_date')) {
                $query->whereBetween('transaction_date', [$request->from_date, $request->to_date]);
            } else if ($request->has('from_date')) {
                $query->where('transaction_date', '>=', $request->from_date);
            } else if ($request->has('to_date')) {
                $query->where('transaction_date', '<=', $request->to_date);
            }
            
            // Apply sorting
            $sortField = $request->input('sort_by', 'transaction_date');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortField, $sortOrder);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $transactions = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $transactions,
                'message' => 'Transactions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in TransactionController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving transactions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validate([
                'amount' => 'required|numeric',
                'description' => 'nullable|string',
                'category_id' => 'nullable|exists:categories,id',
                'type' => 'required|in:income,expense,transfer',
                'client_id' => 'nullable|exists:clients,id',
                'supplier_id' => 'nullable|exists:suppliers,id',
                'transaction_date' => 'required|date',
                'status' => 'required|in:pending,completed,cancelled',
                'payment_method' => 'nullable|string',
                'reference_number' => 'nullable|string'
            ]);

            // Create transaction
            $transaction = new Transaction();
            $transaction->user_id = auth()->id();
            $transaction->amount = $validatedData['amount'];
            $transaction->description = $validatedData['description'] ?? null;
            $transaction->type = $validatedData['type'];
            $transaction->client_id = $validatedData['client_id'] ?? null;
            $transaction->supplier_id = $validatedData['supplier_id'] ?? null;
            $transaction->transaction_date = $validatedData['transaction_date'];
            $transaction->status = $validatedData['status'];
            $transaction->payment_method = $validatedData['payment_method'] ?? null;
            $transaction->reference_number = $validatedData['reference_number'] ?? null;
            $transaction->category_id = $validatedData['category_id'] ?? null;

            $transaction->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'transaction' => $transaction
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $transaction = Transaction::with(['category', 'user', 'supplier', 'client', 'invoices'])
                ->findOrFail($id);
                
            return response()->json([
                'success' => true,
                'data' => $transaction,
                'message' => 'Transaction retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in TransactionController@show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving transaction: ' . $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
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
        try {
            $transaction = Transaction::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'type' => 'sometimes|required|string|in:income,expense,transfer',
                'amount' => 'sometimes|required|numeric|min:0.01',
                'description' => 'nullable|string|max:1000',
                'category_id' => 'nullable|exists:categories,id',
                'supplier_id' => 'nullable|exists:suppliers,id',
                'client_id' => 'nullable|exists:clients,id',
                'transaction_date' => 'sometimes|required|date',
                'status' => 'nullable|string|in:pending,completed,cancelled',
                'payment_method' => 'nullable|string',
                'reference_number' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $transaction->update($validator->validated());
            
            // Load relationships
            $transaction->load(['category', 'user', 'supplier', 'client', 'invoices']);

            try {
                // Enviar evento a Pusher
                $this->pushEvent('transactions', 'transaction-updated', [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'description' => $transaction->description,
                    'category_id' => $transaction->category_id,
                    'supplier_id' => $transaction->supplier_id,
                    'client_id' => $transaction->client_id,
                    'transaction_date' => $transaction->transaction_date,
                    'status' => $transaction->status,
                    'payment_method' => $transaction->payment_method,
                    'reference_number' => $transaction->reference_number,
                    'category' => $transaction->category,
                    'supplier' => $transaction->supplier,
                    'client' => $transaction->client,
                    'invoices' => $transaction->invoices
                ]);
                Log::info('TransactionController@update: Pusher event sent successfully');
            } catch (\Exception $e) {
                Log::error('TransactionController@update: Error sending Pusher event: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'data' => $transaction,
                'message' => 'Transaction updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in TransactionController@update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating transaction: ' . $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $transaction = Transaction::findOrFail($id);
            $transaction->delete();
            
            try {
                // Enviar evento a Pusher
                $this->pushEvent('transactions', 'transaction-deleted', [
                    'id' => $id
                ]);
                Log::info('TransactionController@destroy: Pusher event sent successfully');
            } catch (\Exception $e) {
                Log::error('TransactionController@destroy: Error sending Pusher event: ' . $e->getMessage());
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in TransactionController@destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting transaction: ' . $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
    
    /**
     * Get transaction summary (for dashboard)
     */
    public function summary(Request $request)
    {
        try {
            Log::info('TransactionController@summary: Starting to generate summary');
            
            // Income total - handle NULL values
            $income = Transaction::where('type', 'income')
                ->where('status', '!=', 'cancelled')
                ->when($request->has('from_date'), function($query) use ($request) {
                    return $query->where('transaction_date', '>=', $request->from_date);
                })
                ->when($request->has('to_date'), function($query) use ($request) {
                    return $query->where('transaction_date', '<=', $request->to_date);
                })
                ->sum('amount') ?? 0;
                
            Log::info('TransactionController@summary: Income calculated', ['income' => $income]);
                
            // Expense total - handle NULL values
            $expense = Transaction::where('type', 'expense')
                ->where('status', '!=', 'cancelled')
                ->when($request->has('from_date'), function($query) use ($request) {
                    return $query->where('transaction_date', '>=', $request->from_date);
                })
                ->when($request->has('to_date'), function($query) use ($request) {
                    return $query->where('transaction_date', '<=', $request->to_date);
                })
                ->sum('amount') ?? 0;
                
            Log::info('TransactionController@summary: Expense calculated', ['expense' => $expense]);
                
            // Recent transactions
            $recent = Transaction::with(['category', 'user'])
                ->where('status', '!=', 'cancelled')
                ->orderBy('transaction_date', 'desc')
                ->limit(5)
                ->get();
                
            Log::info('TransactionController@summary: Recent transactions retrieved', ['count' => $recent->count()]);
                
            // Transactions by category - handle NULL category_id values
            $byCategory = Transaction::selectRaw('category_id, sum(amount) as total')
                ->where('status', '!=', 'cancelled')
                ->whereNotNull('category_id') // Skip transactions without category
                ->with('category')
                ->when($request->has('from_date'), function($query) use ($request) {
                    return $query->where('transaction_date', '>=', $request->from_date);
                })
                ->when($request->has('to_date'), function($query) use ($request) {
                    return $query->where('transaction_date', '<=', $request->to_date);
                })
                ->groupBy('category_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get();
                
            Log::info('TransactionController@summary: Transactions by category retrieved', ['count' => $byCategory->count()]);
                
            return response()->json([
                'success' => true,
                'data' => [
                    'income' => (float)$income,
                    'expense' => (float)$expense,
                    'balance' => (float)($income - $expense),
                    'recent_transactions' => $recent,
                    'by_category' => $byCategory
                ],
                'message' => 'Transaction summary retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in TransactionController@summary: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving transaction summary: ' . $e->getMessage()
            ], 500);
        }
    }

    // Dame los montos totales de las transacciones por mes y año
    public function totalAmountByMonthAndYear()
    {
        $transactions = \App\Models\Transaction::query()
            ->selectRaw('
                DATE_PART(\'year\', transaction_date) AS year,
                DATE_PART(\'month\', transaction_date) AS month,
                SUM(amount) AS total
            ')
            ->groupByRaw('DATE_PART(\'year\', transaction_date), DATE_PART(\'month\', transaction_date)')
            ->orderByRaw('DATE_PART(\'year\', transaction_date), DATE_PART(\'month\', transaction_date)')
            ->get();
    
        return response()->json($transactions);
    }
}
