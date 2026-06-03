<?php

namespace App\Http\Controllers;

use App\Models\LedgerAccount;
use Illuminate\Http\Request;

class LedgerAccountController extends Controller
{
    public function index()
    {
        $accounts = LedgerAccount::orderBy('narration', 'asc')->get();
        return response()->json($accounts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'narration' => 'required|string|unique:ledger_accounts,narration|max:255',
            'type' => 'required|in:DR,CR',
        ]);

        $account = LedgerAccount::create($validated);

        return response()->json([
            'message' => 'Ledger Account created successfully',
            'account' => $account
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $account = LedgerAccount::findOrFail($id);
        
        $validated = $request->validate([
            'narration' => 'required|string|max:255|unique:ledger_accounts,narration,' . $id,
            'type' => 'required|in:DR,CR',
        ]);

        $account->update($validated);

        return response()->json([
            'message' => 'Ledger Account updated successfully',
            'account' => $account
        ]);
    }

    public function destroy($id)
    {
        $account = LedgerAccount::findOrFail($id);
        $account->delete();
        
        return response()->json([
            'message' => 'Ledger Account deleted successfully'
        ]);
    }
}
