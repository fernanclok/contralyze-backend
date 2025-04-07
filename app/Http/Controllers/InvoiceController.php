<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Events\PusherEvent;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Invoice::query()
                ->with(['transaction']);
                
            if ($request->has('transaction_id')) {
                $query->where('transaction_id', $request->transaction_id);
            }
            
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            // Apply sorting
            $sortField = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortField, $sortOrder);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $invoices = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $invoices,
                'message' => 'Invoices retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in InvoiceController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving invoices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Log de datos recibidos para depuración
            \Log::info('Request para crear invoice:', $request->all());
            
            $validator = Validator::make($request->all(), [
                'transaction_id' => 'required|exists:transactions,id',
                'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
                'type' => 'required|string|in:receipt,invoice,purchase_order,other',
                'invoice_number' => 'nullable|string|max:100',
                'due_date' => 'nullable|date',
                'notes' => 'nullable|string|max:1000',
                'status' => 'nullable|string|in:pending,paid,overdue,draft'
            ]);

            if ($validator->fails()) {
                \Log::error('Validación fallida:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Verify transaction exists and user has access
            $transaction = Transaction::findOrFail($request->transaction_id);
            \Log::info('Transacción encontrada:', ['id' => $transaction->id]);
            
            // Handle file upload
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = Str::random(20) . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('invoices', $filename, 'public');
                $fileUrl = Storage::url($path);
                \Log::info('Archivo guardado:', ['url' => $fileUrl]);
            } else {
                \Log::error('No se encontró archivo en la petición.');
                return response()->json([
                    'success' => false,
                    'message' => 'No file was uploaded'
                ], 422);
            }
            
            // Create invoice
            $invoice = new Invoice([
                'transaction_id' => $request->transaction_id,
                'file_url' => $fileUrl,
                'type' => $request->type,
                'invoice_number' => $request->invoice_number,
                'due_date' => $request->due_date,
                'notes' => $request->notes,
                'status' => $request->status ?? 'pending'
            ]);
            
            $invoice->save();
            \Log::info('Invoice creada con éxito:', ['id' => $invoice->id]);
            
            // Load transaction relationship
            $invoice->load('transaction');

            // Enviar evento Pusher al crear una factura
            event(new PusherEvent([
                'channel' => 'transactions',
                'event' => 'invoice-created',
                'data' => [
                    'transaction_id' => $request->transaction_id,
                    'invoice' => $invoice
                ]
            ]));

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Invoice uploaded successfully'
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error al crear invoice:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error uploading invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $invoice = Invoice::with('transaction')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Invoice retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in InvoiceController@show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving invoice: ' . $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'transaction_id' => 'sometimes|required|exists:transactions,id',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
                'type' => 'sometimes|required|string|in:receipt,invoice,purchase_order,other',
                'invoice_number' => 'nullable|string|max:100',
                'due_date' => 'nullable|date',
                'notes' => 'nullable|string|max:1000',
                'status' => 'nullable|string|in:pending,paid,overdue,draft'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Handle file upload if a new file is provided
            if ($request->hasFile('file')) {
                // Delete old file if it exists
                $oldPath = str_replace('/storage/', '', $invoice->file_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
                
                // Upload new file
                $file = $request->file('file');
                $filename = Str::random(20) . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('invoices', $filename, 'public');
                $invoice->file_url = Storage::url($path);
            }
            
            // Update other fields
            if ($request->has('transaction_id')) $invoice->transaction_id = $request->transaction_id;
            if ($request->has('type')) $invoice->type = $request->type;
            if ($request->has('invoice_number')) $invoice->invoice_number = $request->invoice_number;
            if ($request->has('due_date')) $invoice->due_date = $request->due_date;
            if ($request->has('notes')) $invoice->notes = $request->notes;
            if ($request->has('status')) $invoice->status = $request->status;
            
            $invoice->save();
            
            // Load transaction relationship
            $invoice->load('transaction');

            // Enviar evento Pusher al actualizar una factura
            event(new PusherEvent([
                'channel' => 'invoices',
                'event' => 'invoice-updated',
                'data' => $invoice
            ]));

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Invoice updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in InvoiceController@update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating invoice: ' . $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            
            // Delete the file from storage
            $path = str_replace('/storage/', '', $invoice->file_url);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            
            $invoice->delete();
            
            // Enviar evento Pusher al eliminar una factura
            event(new PusherEvent([
                'channel' => 'invoices',
                'event' => 'invoice-deleted',
                'data' => ['id' => $id]
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Invoice deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in InvoiceController@destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting invoice: ' . $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
    
    /**
     * Get invoices for a specific transaction
     */
    public function getByTransaction(string $transactionId)
    {
        try {
            $invoices = Invoice::where('transaction_id', $transactionId)
                ->with('transaction')
                ->get();
                
            return response()->json([
                'success' => true,
                'data' => $invoices,
                'message' => 'Invoices retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in InvoiceController@getByTransaction: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving invoices: ' . $e->getMessage()
            ], 500);
        }
    }
}
