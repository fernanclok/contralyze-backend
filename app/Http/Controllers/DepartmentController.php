<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\Department;

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
    
        $departments = Department::where('company_id', $user->company_id)
        ->withCount('users')
        ->get();
    
        return response()->json($departments);
    }

    public function deleteDepartment($id)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $department = Department::find($id);
        $department->delete();

        return response()->json([
            'message' => 'Department deleted successfully',
        ]);
    }

    public function updateDepartment(Request $request, $id)
    {
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
        $department->save();

        return response()->json([
            'message' => 'Department updated successfully',
            'department' => $department,
        ]);
    }
}
