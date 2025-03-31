<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Department;


class RequisitionController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    public function getRequisitions()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($user->role === 'admin') {
            $requisitions = PurchaseRequest::with('user:id,first_name,last_name', 'department:id,name', 'supplier:id,name', 'client:id,name', 'reviewer:id,first_name,last_name')->orderBy('created_at', 'asc')->get();
        } else {
            $requisitions = PurchaseRequest::with('user:id,first_name,last_name', 'department:id,name', 'supplier:id,name', 'client:id,name', 'reviewer:id,first_name,last_name')->where('user_id', $user->id)->orderBy('created_at', 'asc')->get();
        }

        $requisitions = $requisitions->map(function ($requisition) {
            return [
                'id' => $requisition->id,
                'requisition_uid' => $requisition->requisition_uid,
                'title' => $requisition->title,
                'total_amount' => $requisition->total_amount,
                'justification' => $requisition->justification,
                'request_date' => $requisition->request_date,
                'priority' => $requisition->priority,
                'status' => $requisition->status,
                'created_at' => $requisition->created_at,
                'updated_at' => $requisition->updated_at,
                'created_by' => $requisition->user ? [
                    'id' => $requisition->user->id,
                    'first_name' => $requisition->user->first_name,
                    'last_name' => $requisition->user->last_name,
                ] : null,
                'department' => $requisition->department ? [
                    'id' => $requisition->department->id,
                    'name' => $requisition->department->name,
                ] : null,
                'supplier' => $requisition->supplier ? [
                    'id' => $requisition->supplier->id,
                    'name' => $requisition->supplier->name,
                ] : null,
                'client' => $requisition->client ? [
                    'id' => $requisition->client->id,
                    'name' => $requisition->client->name,
                ] : null,
                'reviewed_by' => $requisition->reviewer ? [
                    'id' => $requisition->reviewer->id,
                    'first_name' => $requisition->reviewer->first_name,
                    'last_name' => $requisition->reviewer->last_name,
                ] : null,
                'rejection_reason' => $requisition->rejection_reason,
                'items' => $requisition->items,
            ];
        });

        return response()->json($requisitions);
    }

    public function requisitionDashboard()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $currentMonth = date('m');
        $currentYear = date('Y');

        // Calcular el mes y año anterior
        $previousMonth = $currentMonth - 1;
        $previousYear = $currentYear;

        if ($previousMonth == 0) {
            $previousMonth = 12;
            $previousYear = $currentYear - 1;
        }

        if ($user->role === 'admin') {
            // Contar todas las requisiciones del mes actual si el usuario es administrador
            $totalMonthRequisitions = PurchaseRequest::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $currentMonth)
                ->count();
            $approvedRequisitions = PurchaseRequest::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $currentMonth)
                ->where('status', 'Approved')
                ->count();
            $rejectedRequisitions = PurchaseRequest::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $currentMonth)
                ->where('status', 'Rejected')
                ->count();
            $pendingRequisitions = PurchaseRequest::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $currentMonth)
                ->where('status', 'Pending')
                ->count();

            // Obtener las últimas 5 requisiciones creadas en el sistema en el mes actual
            $last5requisitions = PurchaseRequest::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $currentMonth)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            // Contar todas las requisiciones del mes anterior
            $totalPreviousMonthRequisitions = PurchaseRequest::whereYear('created_at', $previousYear)
                ->whereMonth('created_at', $previousMonth)
                ->count();
            $approvedPreviousMonthRequisitions = PurchaseRequest::whereYear('created_at', $previousYear)
                ->whereMonth('created_at', $previousMonth)
                ->where('status', 'Approved')
                ->count();
            $rejectedPreviousMonthRequisitions = PurchaseRequest::whereYear('created_at', $previousYear)
                ->whereMonth('created_at', $previousMonth)
                ->where('status', 'Rejected')
                ->count();
            $pendingPreviousMonthRequisitions = PurchaseRequest::whereYear('created_at', $previousYear)
                ->whereMonth('created_at', $previousMonth)
                ->where('status', 'Pending')
                ->count();
        } else {
            // Contar las requisiciones del departamento del usuario en el mes actual
            $totalMonthRequisitions = PurchaseRequest::where('department_id', $user->department_id)
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $currentMonth)
                ->count();
            $approvedRequisitions = PurchaseRequest::where('department_id', $user->department_id)
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $currentMonth)
                ->where('status', 'Approved')
                ->count();
            $rejectedRequisitions = PurchaseRequest::where('department_id', $user->department_id)
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $currentMonth)
                ->where('status', 'Rejected')
                ->count();
            $pendingRequisitions = PurchaseRequest::where('department_id', $user->department_id)
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $currentMonth)
                ->where('status', 'Pending')
                ->count();

            // Obtener las últimas 5 requisiciones creadas en el departamento del usuario en el mes actual
            $last5requisitions = PurchaseRequest::where('department_id', $user->department_id)
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $currentMonth)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            // Contar las requisiciones del departamento del usuario en el mes anterior
            $totalPreviousMonthRequisitions = PurchaseRequest::where('department_id', $user->department_id)
                ->whereYear('created_at', $previousYear)
                ->whereMonth('created_at', $previousMonth)
                ->count();
            $approvedPreviousMonthRequisitions = PurchaseRequest::where('department_id', $user->department_id)
                ->whereYear('created_at', $previousYear)
                ->whereMonth('created_at', $previousMonth)
                ->where('status', 'Approved')
                ->count();
            $rejectedPreviousMonthRequisitions = PurchaseRequest::where('department_id', $user->department_id)
                ->whereYear('created_at', $previousYear)
                ->whereMonth('created_at', $previousMonth)
                ->where('status', 'Rejected')
                ->count();
            $pendingPreviousMonthRequisitions = PurchaseRequest::where('department_id', $user->department_id)
                ->whereYear('created_at', $previousYear)
                ->whereMonth('created_at', $previousMonth)
                ->where('status', 'Pending')
                ->count();
        }

        return response()->json([
            'id' => 1,
            'total_month_requisitions' => $totalMonthRequisitions,
            'total_previous_month_requisitions' => $totalPreviousMonthRequisitions,
            'approved_requisitions' => $approvedRequisitions,
            'approved_previous_month_requisitions' => $approvedPreviousMonthRequisitions,
            'rejected_requisitions' => $rejectedRequisitions,
            'rejected_previous_month_requisitions' => $rejectedPreviousMonthRequisitions,
            'pending_requisitions' => $pendingRequisitions,
            'pending_previous_month_requisitions' => $pendingPreviousMonthRequisitions,
            'last_5_requisitions' => $last5requisitions,
        ]);
    }

    public function createRequisition(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $department = Department::find($user->department_id);

        if (!$department || !$department->isActive) {
            return response()->json([
                'errors' => [
                    'server' => 'Your department is inactive. You cannot create a requisition.'
                ]
            ], 403);
        }

        // Obtener el año actual
        $currentYear = date('Y');

        // Contar el número de requisiciones creadas por el departamento en el año actual
        $requisitionCount = PurchaseRequest::whereYear('created_at', $currentYear)
            ->where('department_id', $user->department_id)
            ->count();

        // Obtener iniciales del departamento
        $departmentInitials = strtoupper(substr($department->name, 0, 1));

        // Generar el UID personalizado con el número correcto para el departamento
        $requisitionUid = sprintf('REQ-%s-%s-%03d', $departmentInitials, $currentYear, $requisitionCount + 1);

        $requisition = new PurchaseRequest();
        $requisition->requisition_uid = $requisitionUid;
        $requisition->title = $request->title;
        $requisition->justification = $request->justification;
        $requisition->total_amount = $request->total_amount;
        $requisition->request_date = $request->request_date;
        $requisition->priority = $request->priority;
        $requisition->status = "Pending";
        $requisition->items = $request->items;
        $requisition->user_id = $user->id;
        $requisition->department_id = $user->department_id;
        $requisition->supplier_id = $request->supplier_id;
        $requisition->client_id = $request->client_id;
        $requisition->save();

        // manejo de archivos adjuntos
        if ($request->hasFile('attachment')) {
            foreach ($request->file('attachment') as $attachment) {
                $path = $attachment->store('attachments', 'public');
                PurchaseRequestAttachment::create([
                    'attachment' => $path,
                    'purchase_request_id' => $requisition->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Requisition created successfully',
            'requisition' => $requisition,
        ], 201);
    }

    public function updateRequisition(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $requisition = PurchaseRequest::find($id);

        if (!$requisition) {
            return response()->json(['error' => 'Requisition not found'], 404);
        }

        if ($requisition->status !== 'Pending') {
            return response()->json(['error' => 'Requisition cannot be updated'], 400);
        }

        $requisition->title = $request->title;
        $requisition->justification = $request->justification;
        $requisition->total_amount = $request->total_amount;
        $requisition->request_date = $request->request_date;
        $requisition->priority = $request->priority;
        $requisition->items = $request->items;
        $requisition->supplier_id = $request->supplier_id;
        $requisition->client_id = $request->client_id;
        $requisition->save();

        // manejo de archivos adjuntos
        if ($request->hasFile('attachment')) {
            foreach ($request->file('attachment') as $attachment) {
                $path = $attachment->store('attachments', 'public');
                PurchaseRequestAttachment::create([
                    'attachment' => $path,
                    'purchase_request_id' => $requisition->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Requisition updated successfully',
            'requisition' => $requisition,
        ]);
    }

    public function approveRequisition(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $requisition = PurchaseRequest::find($id);

        if (!$requisition) {
            return response()->json(['error' => 'Requisition not found'], 404);
        }

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $requisition->status = 'Approved';
        $requisition->reviewed_by = $user->id;
        $requisition->save();

        return response()->json([
            'message' => 'Requisition approved successfully',
            'requisition' => $requisition,
        ]);
    }

    public function rejectRequisition(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $requisition = PurchaseRequest::find($id);

        if (!$requisition) {
            return response()->json(['error' => 'Requisition not found'], 404);
        }

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $requisition->status = 'Rejected';
        $requisition->rejection_reason = $request->rejection_reason;
        $requisition->reviewed_by = $user->id;
        $requisition->save();

        return response()->json([
            'message' => 'Requisition rejected successfully',
            'requisition' => $requisition,
        ]);
    }
}
