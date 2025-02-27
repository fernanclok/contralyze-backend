<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\Category;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }

    public function createCategory(Request $request)
    {
        $validator = validator::make($request->all(), [
            'name' => 'required|string',
            'type' => 'required|string',
            'department_id' => 'required|integer|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $category = new Category();
        $category->name = $request->name;
        $category->type = $request->type;
        $category->department_id = $request->department_id;
        $category->company_id = $user->company_id;
        $category->save();

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category,
        ], 200);
    }

    public function getCategories()
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $categories = Category::where('company_id', $user->company_id)
            ->get();

        return response()->json($categories);
    }
}
