<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    // Create a new supplier
    public function createSupplier(Request $request)
    {
        $supplier = new Supplier();
        $supplier->name = $request->name;
        $supplier->email = $request->email;
        $supplier->phone = $request->phone;
        $supplier->address = $request->address;
        $supplier->company_name = $request->company_name;
        $supplier->created_by = Auth::id();
        $supplier->save();

        return response()->json([
            'message' => 'Supplier created successfully',
            'supplier' => $supplier,
        ], 201);
    }
    
    public function allSuppliersbyUser($id)
    {

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($user->role === 'admin') {
            $suppliers = Supplier::all();
        } else {
            $suppliers = Supplier::where('created_by', $id)->get();
        }

        return response()->json($suppliers);
    }

    // Update a supplier
    public function updateSupplier(Request $request, $id)
    {
        $supplier = Supplier::find($id);
        $supplier->name = $request->name;
        $supplier->email = $request->email;
        $supplier->phone = $request->phone;
        $supplier->address = $request->address;
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
