<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\Company;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    // get company info by user
    public function companyInfo()
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $company = Company::find($user->company_id);

        return response()->json($company);
    }

    // get all users in company
    public function allUsers()
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $users = Company::find($user->company_id)->users;

        return response()->json($users);
    }
}
