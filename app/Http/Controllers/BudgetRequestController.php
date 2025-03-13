<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BudgetRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class BudgetRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // Consulta inicial con relaciones
            $query = BudgetRequest::query()->with(['category', 'reviewer', 'user']);
            
            // Verificar si se solicitan relaciones específicas
            if ($request->has('include')) {
                $includes = explode(',', $request->input('include'));
                $query->with($includes);
            }
            
            // Si se proporciona un user_id, filtrar por ese usuario
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }
            
            $requests = $query->orderBy('request_date', 'desc')
                ->get();
                
            // Transformar los resultados para asegurar que se incluye toda la información del usuario
            $requests = $requests->map(function ($request) {
                // Si no hay información del usuario, obtenerla manualmente
                if (!$request->user || !isset($request->user->name)) {
                    $user = User::find($request->user_id);
                    if ($user) {
                        $request->user = [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'first_name' => $user->first_name,
                            'last_name' => $user->last_name
                        ];
                    }
                }
                return $request;
            });

            Log::info('BudgetRequestController@index: Retrieved ' . $requests->count() . ' requests');

            return response()->json(['requests' => $requests]);
        } catch (\Exception $e) {
            Log::error('Error en BudgetRequestController@index: ' . $e->getMessage());
            return response()->json(['error' => 'Error obteniendo las solicitudes: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'requested_amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'status' => 'sometimes|in:pending,approved,rejected'
        ]);

        // Asignar el ID del usuario actual
        $validated['user_id'] = auth()->id();
        
        // Establecer estado pendiente por defecto
        if (!isset($validated['status'])) {
            $validated['status'] = 'pending';
        }
        
        // Establecer fecha de solicitud
        $validated['request_date'] = now();
        
        $budgetRequest = BudgetRequest::create($validated);
        
        // Cargar las relaciones para devolverlas en la respuesta
        $budgetRequest->load(['category', 'user']);

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
