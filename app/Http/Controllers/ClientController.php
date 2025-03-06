<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }
    
    public function createClient(Request $request)
    {
        $client = new Client();
        $client->name = $request->name;
        $client->email = $request->email;
        $client->phone = $request->phone;
        $client->address = $request->address;
        $client->isActive = true;
        $client->created_by = Auth::id();
        $client->save();

        return response()->json([
            'message' => 'Client created successfully',
            'client' => $client,
        ], 201);
    }

    public function allClients()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($user->role === 'admin') {
            $clients = Client::with('creator:id,first_name,last_name')->get();
        } else {
            $clients = Client::with('creator:id,first_name,last_name')->where('created_by', $user->id)->get();
        }

        $clients = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'isActive' => $client->isActive,
                'created_by' => $client->creator ? [
                    'id' => $client->creator->id,
                    'first_name' => $client->creator->first_name,
                    'last_name' => $client->creator->last_name,
                ] : null,
            ];
        });

        return response()->json($clients);
    }
    
    public function updateClient(Request $request, $id)
    {
        $client = Client::find($id);

        $isActive = filter_var($request->isActive, FILTER_VALIDATE_BOOLEAN);

        $client->name = $request->name;
        $client->email = $request->email;
        $client->phone = $request->phone;
        $client->address = $request->address;
        $client->isActive = $isActive;
        $client->save();

        return response()->json([
            'message' => 'Client updated successfully',
            'client' => $client,
        ]);
    }
}
