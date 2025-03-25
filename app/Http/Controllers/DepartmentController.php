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
    
        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    
        // Obtener los departamentos con el conteo de usuarios
        $departments = Department::withCount('users')->get();
    
        // Transformar los datos para incluir el conteo de usuarios
        $departments = $departments->map(function ($department) {
            return [
                'id' => $department->id,
                'name' => $department->name,
                'description' => $department->description,
                'isActive' => $department->isActive,
                'company_id' => $department->company_id,
                'created_at' => $department->created_at,
                'updated_at' => $department->updated_at,
                'users_count' => $department->users_count, // Conteo de usuarios
            ];
        });
    
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
