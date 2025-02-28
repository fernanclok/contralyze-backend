<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use App\Models\User;
use App\Models\Company;
use App\Models\Department;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string',
            'company_email' => 'required|email|unique:companies,email',
            'company_phone' => 'required|string',
            'company_address' => 'required|string',
            'company_city' => 'required|string',
            'company_state' => 'required|string',
            'company_zip' => 'required|string',
            'company_size' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string',
            'role' => 'in:admin,user',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            if ($errors->has('company_email')) {
                return response()->json(['message' => 'Company email already registered'], 400);
            }
            if ($errors->has('email')) {
                return response()->json(['message' => 'User email already registered'], 400);
            }
            return response()->json($errors, 400);
        }

        // Crear la empresa
        $company = new Company();
        $company->name = $request->company_name;
        $company->email = $request->company_email;
        $company->phone = $request->company_phone;
        $company->address = $request->company_address;
        $company->city = $request->company_city;
        $company->state = $request->company_state;
        $company->zip = $request->company_zip;
        $company->size = $request->company_size;
        $company->save();

        // Crear el departamento por defecto
        $department = new Department();
        $department->name = 'Admin';
        $department->description = 'Admin department';
        $department->company_id = $company->id;
        $department->save();

        // Crear el usuario y asignarle la empresa
        $user = new User();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->role = 'admin'; // Por defecto admin
        $user->status = 'active'; // Por defecto activo
        $user->is_first_user = true; // Indicar que es el primer usuario
        $user->company_id = $company->id; // Asignar la empresa creada
        $user->department_id = $department->id; // Asignar el departamento creado
        $user->created_by = null;
        $user->save();

        return response()->json([
            'message' => 'User and Company created successfully',
            'user' => $user,
            'company' => $company,
            'token' => $this->respondWithToken(Auth::login($user)),
        ], 201);
    }


    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $credentials = $request->only('email', 'password');

        if (!$token = Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Auth::user();

        if ($user->status === 'inactive') {
            // Invalidar el token generado
            Auth::logout();
            return response()->json(['message' => 'Your account is inactive. Please contact support.'], 403);
        }

        return response()->json([
            'user' => $user,
            'token' => $this->respondWithToken($token),
        ], 200);
    }

    public function refresh()
    {
        return $this->respondWithToken(Auth::refresh());
    }

    public function logout(Request $request)
    {
        auth()->logout();
        return response()->json(['message' => 'User logged out and token invalidated'], 200);
    }

    public function me()
    {
        return response()->json(Auth::user());
    }

    public function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
        ]);
    }
}
