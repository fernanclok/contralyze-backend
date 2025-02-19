<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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
    
        // Crear el usuario y asignarle la empresa
        $user = new User();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->role = 'admin'; // Por defecto admin
        $user->company_id = $company->id; // Asignar la empresa creada
        $user->created_by = null;
        $user->save();
    
        return response()->json([
            'message' => 'User and Company created successfully',
            'user' => $user,
            'company' => $company
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

        return response()->json([
            'user' => $user,
            'token' => $token,
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
