<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }

    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string',
            'department_id' => 'required|integer|exists:departments,id',
            'role' => 'in:admin,user',
            'photo_profile_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            // Registrar los errores en los logs de Laravel
            Log::error('Validation errors: ', $validator->errors()->toArray());

            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtener el usuario autenticado
        $admin = Auth::user();

        if (!$admin || $admin->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Usar el company_id del admin
        $user = new User();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->role = $request->role;
        $user->status = 'active';
        if ($request->hasFile('photo_profile')) {
            $path = $request->file('photo_profile')->store('profile_photos', 'public');
            $user->photo_profile_path = $path;
        }
        $user->company_id = $admin->company_id; // Se asigna el mismo company_id del admin
        $user->department_id = $admin->department_id;
        $user->created_by = $admin->id;
        $user->save();

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ], 200);
    }

    public function allUsers()
    {
        $users = User::all();

        return response()->json($users);
    }

    public function updateUser(Request $request, $id)
    {
        Log::info('Request: ', $request->all());
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'password' => 'nullable|string|min:6',
            'role' => 'in:admin,user',
            'status' => 'in:active,inactive',
            'photo_profile_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'department_id' => 'required|integer|exists:departments,id',

        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Obtener el usuario autenticado
        $admin = Auth::user();

        if (!$admin || $admin->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::find($id);
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->role = $request->role;
        $user->status = $request->status;
        $user->department_id = $request->department_id;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        if ($request->hasFile('photo_profile')) {
            // Eliminar la foto anterior si existe
            if ($user->photo_profile_path) {
                Storage::disk('public')->delete($user->photo_profile_path);
            }
            $path = $request->file('photo_profile')->store('profile_photos', 'public');
            $user->photo_profile_path = $path;
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }
}
