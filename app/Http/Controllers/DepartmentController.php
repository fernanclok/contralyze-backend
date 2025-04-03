<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\Department;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }

    public function createDepartment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department_name' => 'required|string',
            'department_description' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Obtener el usuario autenticado
        $admin = Auth::user();

        if (!$admin || $admin->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $department = new Department();
        $department->name = $request->department_name;
        $department->description = $request->department_description;
        $department->isActive = true;
        $department->company_id = $admin->company_id;
        $department->save();

        return response()->json([
            'message' => 'Department created successfully',
            'department' => $department,
        ], 200);
    }

    public function allDepartments()
    {
        $user = Auth::user();

        // Si el usuario no está autenticado, devolver error
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Si el usuario es administrador, devolver todos los departamentos
        if ($user->role === 'admin') {
            $departments = Department::withCount('users')->get();
        } else {
            // Si no es administrador, devolver solo el departamento asociado al usuario
            $departments = Department::where('id', $user->department_id)->get();
        }

        return response()->json($departments);
    }

    public function updateDepartment(Request $request, $id)
    {
        Log::info('Request: ', $request->all());
        $validator = Validator::make($request->all(), [
            'department_name' => 'required|string',
            'department_description' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $department = Department::find($id);
        $department->name = $request->department_name;
        $department->description = $request->department_description;
        $department->isActive = $request->isActive;
        $department->save();

        return response()->json([
            'message' => 'Department updated successfully',
            'department' => $department,
        ]);
    }

    // // get all departaments by user and company
    // public function allDepartmentsByUser($id)
    // {
    //     $user = Auth::user();

    //     if (!$user || $user->role !== 'admin') {
    //         return response()->json(['error' => 'Unauthorized'], 403);
    //     }
    //     log($user->company_id);

    //     $departments = Department::where('company_id', $user->company_id)
    //         // get the departments in order of the last created
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     return response()->json($departments);
    // }
}
