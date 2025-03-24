<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactions = Transaction::all();
        return response()->json($transactions);
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
        $transaction = Transaction::create($request->all());
        return response()->json($transaction, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $transaction = Transaction::findOrFail($id);
        return response()->json($transaction);
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
        $transaction = Transaction::findOrFail($id);
        $transaction->update($request->all());
        return response()->json($transaction);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->delete();
        return response()->json(null, 204);
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
