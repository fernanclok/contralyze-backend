<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use App\Models\Department;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    // Create a new supplier
    public function createSupplier(Request $request)
    {
        $user = Auth::user();

        $department = Department::find($user->department_id);

        if (!$department || !$department->isActive) {
            return response()->json([
                'errors' => [
                    'server' => 'Your department is inactive. You cannot create a Supplier.'
                ]
            ], 403);
        }

        if(Supplier::where('email', $request->email)->exists()) {
            return response()->json([
                'errors' => ['server' => 'The email is already registered'] ,
            ], 422);
        }

        $supplier = new Supplier();
        $supplier->name = $request->name;
        $supplier->email = $request->email;
        $supplier->phone = $request->phone;
        $supplier->address = $request->address;
        $supplier->isActive = true;
        $supplier->created_by = $user->id;
        $supplier->save();

        return response()->json([
            'message' => 'Supplier created successfully',
            'supplier' => $supplier,
        ], 201);
    }
    
    public function allSuppliers()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($user->role === 'admin') {
            $suppliers = Supplier::with('creator:id,first_name,last_name')->get();
        } else {
            $suppliers = Supplier::with('creator:id,first_name,last_name')->where('created_by', $user->id)->get();
        }

        $suppliers = $suppliers->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'address' => $supplier->address,
                'isActive' => $supplier->isActive,
                'created_by' => $supplier->creator ? [
                    'id' => $supplier->creator->id,
                    'first_name' => $supplier->creator->first_name,
                    'last_name' => $supplier->creator->last_name,
                ] : null,
            ];
        });

        return response()->json($suppliers);
    }

    // Update a supplier
    public function updateSupplier(Request $request, $id)
    {
        $supplier = Supplier::find($id);

        $isActive = filter_var($request->isActive, FILTER_VALIDATE_BOOLEAN);

        $supplier->name = $request->name;
        $supplier->email = $request->email;
        $supplier->phone = $request->phone;
        $supplier->address = $request->address;
        $supplier->isActive = $isActive;
        $supplier->save();

        return response()->json([
            'message' => 'Supplier updated successfully',
            'supplier' => $supplier,
        ]);
    }


    // Delete a supplier
    public function deleteSupplier($id)
    {
        $supplier = Supplier::find($id);
        $supplier->delete();

        return response()->json([
            'message' => 'Supplier deleted successfully',
        ]);
    }
}
