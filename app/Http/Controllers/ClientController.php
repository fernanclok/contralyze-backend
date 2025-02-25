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
        $client->created_by = Auth::id();
        $client->save();

        return response()->json([
            'message' => 'Client created successfully',
            'client' => $client,
        ], 201);
    }

    public function allClients()
    {
        $clients = Client::all();

        return response()->json($clients);
    }
    public function allClientsbyUser($id)
    {
        $clients = Client::where('created_by', $id)->get();

        return response()->json($clients);
    }
    public function updateClient(Request $request, $id)
    {
        $client = Client::find($id);
        $client->name = $request->name;
        $client->email = $request->email;
        $client->phone = $request->phone;
        $client->address = $request->address;
        $client->save();

        return response()->json([
            'message' => 'Client updated successfully',
            'client' => $client,
        ]);
    }
    public function deleteClient($id)
    {
        $client = Client::find($id);
        $client->delete();

        return response()->json([
            'message' => 'Client deleted successfully',
        ]);
    }
}
