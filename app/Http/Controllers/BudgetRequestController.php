<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BudgetRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use App\Traits\UsesPusher;

class BudgetRequestController extends Controller
{
    use UsesPusher;

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

            // Transformar los resultados para asegurar que se incluya toda la información del usuario
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
            return response()->json(['error' => 'Error obtaining requests: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('BudgetRequestController@store: Starting request validation');

            $validated = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'requested_amount' => [
                    'required',
                    'numeric',
                    'min:0.01',
                    'max:999999999.99',
                    'regex:/^\d+(\.\d{1,2})?$/'
                ],
                'description' => [
                    'required',
                    'string',
                    'min:10',
                    'max:1000',
                    'regex:/^[a-zA-Z0-9\s\-_.,!?()áéíóúÁÉÍÓÚñÑ]+$/'
                ],
                'status' => 'sometimes|in:pending,approved,rejected'
            ], [
                'requested_amount.regex' => 'Amount must have a maximum of 2 decimal places',
                'description.regex' => 'Description can only contain letters, numbers, and basic punctuation marks',
                'description.min' => 'Description must be at least 10 characters long',
                'description.max' => 'Description cannot exceed 1000 characters'
            ]);

            Log::info('BudgetRequestController@store: Validation passed', ['data' => $validated]);

            // Sanitizar la descripción
            $validated['description'] = strip_tags($validated['description']);

            // Asignar el ID del usuario actual
            $user = auth()->user();
            if (!$user) {
                Log::error('BudgetRequestController@store: No authenticated user found');
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $validated['user_id'] = $user->id;
            Log::info('BudgetRequestController@store: User assigned', ['user_id' => $user->id]);

            // Establecer estado pendiente por defecto
            if (!isset($validated['status'])) {
                $validated['status'] = 'pending';
            }

            // Establecer fecha de solicitud
            $validated['request_date'] = now();

            Log::info('BudgetRequestController@store: Creating budget request', ['data' => $validated]);

            $budgetRequest = BudgetRequest::create($validated);

            // Cargar las relaciones para devolverlas en la respuesta
            $budgetRequest->load(['category', 'user']);

            Log::info('BudgetRequestController@store: Budget request created successfully', ['id' => $budgetRequest->id]);

            try {
                // Enviar evento a Pusher usando el trait
                $this->pushEvent('budget-requests', 'new-request', [
                    'budget_request' => $budgetRequest
                ]);
                Log::info('BudgetRequestController@store: Pusher event sent successfully');
            } catch (\Exception $e) {
                Log::error('BudgetRequestController@store: Error sending Pusher event: ' . $e->getMessage());
                // No retornamos error aquí porque el budget request ya se creó correctamente
            }

            return response()->json([
                'message' => 'Budget request created successfully',
                'request' => $budgetRequest
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('BudgetRequestController@store: Validation error', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('BudgetRequestController@store: Error creating budget request: ' . $e->getMessage());
            return response()->json(['error' => 'Error creating budget request: ' . $e->getMessage()], 500);
        }
    }
    // Removed misplaced route definition

    public function approve($id)
    {
        $user = auth()->user();
        // Only administrators can approve budget requests
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Only administrators can approve budget requests.'], 403);
        }

        $budgetRequest = BudgetRequest::with(['user.department', 'category'])->findOrFail($id);

        // If request is already approved, return error
        if ($budgetRequest->status === 'approved') {
            return response()->json(['error' => 'This request has already been approved.'], 400);
        }

        $requestedAmount = $budgetRequest->requested_amount;

        // Check total available budget for the category
        $totalBudget = \App\Models\Budget::where('status', 'active')
            ->where('category_id', $budgetRequest->category_id)
            ->sum('max_amount');

        $totalApproved = BudgetRequest::where('status', 'approved')
            ->where('category_id', $budgetRequest->category_id)
            ->sum('requested_amount');

        $totalAvailable = $totalBudget - $totalApproved;

        // Validate total budget availability
        if ($totalAvailable < $requestedAmount) {
            return response()->json([
                'error' => 'Not enough total budget available',
                'requested' => $requestedAmount,
                'available' => max(0, $totalAvailable),
                'budget_type' => 'total'
            ], 400);
        }

        // Check department budget if the request belongs to a department
        if ($budgetRequest->category && $budgetRequest->category->department_id) {
            $departmentId = $budgetRequest->category->department_id;

            // Get available budget for the department
            $departmentBudget = \App\Models\Budget::where('status', 'active')
                ->whereHas('category', function ($query) use ($departmentId) {
                    $query->where('department_id', $departmentId);
                })
                ->where('category_id', $budgetRequest->category_id)
                ->sum('max_amount');

            // Get approved amount for the department
            $departmentApproved = BudgetRequest::where('status', 'approved')
                ->where('category_id', $budgetRequest->category_id)
                ->whereHas('category', function ($query) use ($departmentId) {
                    $query->where('department_id', $departmentId);
                })
                ->sum('requested_amount');

            $departmentAvailable = $departmentBudget - $departmentApproved;

            // Validate department budget availability
            if ($departmentAvailable < $requestedAmount) {
                return response()->json([
                    'error' => 'Not enough department budget available',
                    'requested' => $requestedAmount,
                    'available' => max(0, $departmentAvailable),
                    'department' => $budgetRequest->category->department->name ?? 'Unknown Department',
                    'budget_type' => 'department'
                ], 400);
            }
        }

        // If all validations pass, approve the request
        $budgetRequest->status = 'approved';
        $budgetRequest->reviewed_by = $user->id;
        $budgetRequest->save();

        // Prepare response with remaining budget information
        $responseData = [
            'message' => 'Budget request approved successfully',
            'request' => $budgetRequest,
            'budget_info' => [
                'requested_amount' => $requestedAmount,
                'total_budget' => [
                    'before' => $totalAvailable,
                    'after' => $totalAvailable - $requestedAmount
                ]
            ]
        ];

        // Add department budget info if available
        if (isset($departmentAvailable)) {
            $responseData['budget_info']['department_budget'] = [
                'name' => $budgetRequest->category->department->name ?? 'Unknown Department',
                'before' => $departmentAvailable,
                'after' => $departmentAvailable - $requestedAmount
            ];
        }

        // Enviar evento a Pusher usando el trait
        $this->pushEvent('budget-requests', 'request-approved', [
            'budget_request' => $budgetRequest
        ]);

        return response()->json($responseData);
    }

    public function reject($id)
    {
        try {
            Log::info('BudgetRequestController@reject: Starting request rejection', ['id' => $id]);

            // Verificar si el usuario es administrador
            $user = auth()->user();
            if ($user->role !== 'admin') {
                Log::warning('BudgetRequestController@reject: Unauthorized attempt', [
                    'user_id' => $user->id,
                    'user_role' => $user->role
                ]);
                return response()->json(['error' => 'Unauthorized. Only administrators can reject budget requests.'], 403);
            }

            $budgetRequest = BudgetRequest::with(['category.department', 'user', 'reviewer'])->findOrFail($id);

            // Verificar si ya está rechazada
            if ($budgetRequest->status === 'rejected') {
                Log::info('BudgetRequestController@reject: Request already rejected', ['id' => $id]);
                return response()->json(['error' => 'Budget request is already rejected.'], 400);
            }

            // Verificar si ya está aprobada
            if ($budgetRequest->status === 'approved') {
                Log::info('BudgetRequestController@reject: Cannot reject approved request', ['id' => $id]);
                return response()->json(['error' => 'Cannot reject an approved budget request.'], 400);
            }

            $budgetRequest->status = 'rejected';
            $budgetRequest->reviewed_by = $user->id;
            $budgetRequest->save();

            // Recargar el modelo con las relaciones después de guardar
            $budgetRequest->refresh();
            $budgetRequest->load(['category.department', 'user', 'reviewer']);

            Log::info('BudgetRequestController@reject: Request rejected successfully', [
                'id' => $id,
                'reviewer_id' => $user->id
            ]);

            // Enviar evento a Pusher usando el trait
            $this->pushEvent('budget-requests', 'request-rejected', [
                'budget_request' => $budgetRequest
            ]);

            return response()->json([
                'message' => 'Budget request rejected successfully',
                'request' => $budgetRequest
            ]);
        } catch (\Exception $e) {
            Log::error('BudgetRequestController@reject: Error rejecting request', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Error rejecting budget request: ' . $e->getMessage()
            ], 500);
        }
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
            'requested_amount' => [
                'sometimes',
                'required',
                'numeric',
                'min:0.01',
                'max:999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/'
            ],
            'description' => [
                'sometimes',
                'required',
                'string',
                'min:10',
                'max:1000',
                'regex:/^[a-zA-Z0-9\s\-_.,!?()áéíóúÁÉÍÓÚñÑ]+$/'
            ],
        ];

        // Solo los admin pueden cambiar el status
        if ($user->role === 'admin') {
            $validationRules['status'] = 'sometimes|required|in:pending,approved,rejected';
        }

        $validated = $request->validate($validationRules, [
            'requested_amount.regex' => 'El monto debe tener máximo 2 decimales',
            'description.regex' => 'La descripción solo puede contener letras, números y signos de puntuación básicos',
            'description.min' => 'La descripción debe tener al menos 10 caracteres',
            'description.max' => 'La descripción no puede exceder los 1000 caracteres'
        ]);

        // Si no es admin y está intentando cambiar el status, ignorar ese campo
        if ($user->role !== 'admin' && $request->has('status')) {
            unset($validated['status']);
        }

        // Sanitizar la descripción si está presente
        if (isset($validated['description'])) {
            $validated['description'] = strip_tags($validated['description']);
        }

        $budgetRequest->update($validated);

        // Enviar evento a Pusher usando el trait
        $this->pushEvent('budget-requests', 'request-updated', [
            'budget_request' => $budgetRequest
        ]);

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
