<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;

class BankController extends Controller
{
    public function index()
    {
        return response()->json(Bank::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'account_number' => 'nullable|string',
            'branch' => 'nullable|string'
        ]);

        $bank = Bank::create($request->all());
        return response()->json($bank, 201);
    }

    public function destroy($id)
    {
        Bank::destroy($id);
        return response()->json(null, 204);
    }
}
