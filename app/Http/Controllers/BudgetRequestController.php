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
        // Verificar si el usuario es administrador
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Only administrators can approve budget requests.'], 403);
        }

        $budgetRequest = BudgetRequest::with(['user.department', 'category'])->findOrFail($id);
        
        // Si la solicitud ya fue aprobada, retornar error
        if ($budgetRequest->status === 'approved') {
            return response()->json(['error' => 'La solicitud ya ha sido aprobada.'], 400);
        }
        
        // Obtener el monto solicitado
        $requestedAmount = $budgetRequest->requested_amount;
        
        // Verificar si hay presupuesto disponible total
        $totalBudget = \App\Models\Budget::where('status', 'active')
            ->where('category_id', $budgetRequest->category_id)
            ->sum('max_amount');
            
        // Obtener el total gastado en solicitudes aprobadas
        $totalApproved = BudgetRequest::where('status', 'approved')
            ->where('category_id', $budgetRequest->category_id)
            ->sum('requested_amount');
            
        $totalAvailable = $totalBudget - $totalApproved;
        
        if ($totalAvailable < $requestedAmount) {
            return response()->json([
                'error' => 'No hay suficiente presupuesto total disponible',
                'requested' => $requestedAmount,
                'available' => $totalAvailable
            ], 400);
        }

        // Calcular el presupuesto total restante después de aprobar esta solicitud
        $remainingTotalBudget = $totalAvailable - $requestedAmount;
        
        // Variables para el presupuesto departamental
        $departmentAvailable = null;
        $remainingDepartmentBudget = null;
        $departmentName = null;
        
        // Si el usuario tiene un departamento asignado, verificar el presupuesto departamental
        if ($budgetRequest->user && $budgetRequest->user->department_id) {
            $departmentId = $budgetRequest->user->department_id;
            $departmentName = $budgetRequest->user->department->name;
            
            // Obtener el presupuesto disponible para el departamento
            $departmentUsers = \App\Models\User::where('department_id', $departmentId)->pluck('id');
            
            // Presupuesto total asignado al departamento
            $departmentBudget = \App\Models\Budget::whereIn('user_id', $departmentUsers)
                ->where('status', 'active')
                ->where('category_id', $budgetRequest->category_id)
                ->sum('max_amount');
                
            // Presupuesto ya aprobado para el departamento
            $departmentApproved = BudgetRequest::whereIn('user_id', $departmentUsers)
                ->where('status', 'approved')
                ->where('category_id', $budgetRequest->category_id)
                ->sum('requested_amount');
                
            $departmentAvailable = $departmentBudget - $departmentApproved;
            
            if ($departmentAvailable < $requestedAmount) {
                return response()->json([
                    'error' => 'No hay suficiente presupuesto departamental disponible',
                    'requested' => $requestedAmount,
                    'available' => $departmentAvailable,
                    'department' => $departmentName
                ], 400);
            }
            
            // Calcular el presupuesto departamental restante después de aprobar esta solicitud
            $remainingDepartmentBudget = $departmentAvailable - $requestedAmount;
        }
        
        // Si pasa todas las verificaciones, aprobar la solicitud
        $budgetRequest->status = 'approved';
        $budgetRequest->reviewed_by = auth()->id();
        $budgetRequest->save();

        // Preparar la respuesta con la información del presupuesto restante
        $responseData = [
            'message' => 'Budget request approved successfully',
            'request' => $budgetRequest,
            'budget_info' => [
                'requested_amount' => $requestedAmount,
                'total_budget' => [
                    'before' => $totalAvailable,
                    'after' => $remainingTotalBudget
                ]
            ]
        ];
        
        // Agregar información del departamento si está disponible
        if ($departmentAvailable !== null) {
            $responseData['budget_info']['department_budget'] = [
                'name' => $departmentName,
                'before' => $departmentAvailable,
                'after' => $remainingDepartmentBudget
            ];
        }

        return response()->json($responseData);
    }

    public function reject($id)
    {
        // Verificar si el usuario es administrador
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Only administrators can reject budget requests.'], 403);
        }

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
        $budgetRequest = BudgetRequest::findOrFail($id);
        $user = auth()->user();
        
        // Solo permitir que los admin o el creador de la solicitud puedan actualizarla
        if ($user->role !== 'admin' && $budgetRequest->user_id !== $user->id) {
            return response()->json(['error' => 'You are not authorized to update this budget request.'], 403);
        }
        
        // Si el usuario no es admin, eliminar el campo 'status' de la validación
        $validationRules = [
            'category_id' => 'sometimes|required|exists:categories,id',
            'requested_amount' => 'sometimes|required|numeric|min:0',
            'description' => 'sometimes|required|string',
        ];
        
        // Solo los admin pueden cambiar el status
        if ($user->role === 'admin') {
            $validationRules['status'] = 'sometimes|required|in:pending,approved,rejected';
        }
        
        $validated = $request->validate($validationRules);
        
        // Si no es admin y está intentando cambiar el status, ignorar ese campo
        if ($user->role !== 'admin' && $request->has('status')) {
            unset($validated['status']);
        }

        $budgetRequest->update($validated);

        return response()->json([
            'message' => 'Budget request updated successfully',
            'request' => $budgetRequest
        ]);
    }

    public function destroy(string $id)
    {
        $budgetRequest = BudgetRequest::findOrFail($id);
        $user = auth()->user();
        
        // Solo permitir que los admin o el creador de la solicitud puedan eliminarla
        if ($user->role !== 'admin' && $budgetRequest->user_id !== $user->id) {
            return response()->json(['error' => 'You are not authorized to delete this budget request.'], 403);
        }
        
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
