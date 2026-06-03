<?php

namespace App\Http\Controllers;

use App\Models\LedgerTransaction;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function mainLedger(Request $request)
    {
        $query = LedgerTransaction::with('user', 'bank')->where('type', 'MAIN');
        return response()->json($query->orderBy('id', 'desc')->get());
    }

    public function officerLedger(Request $request)
    {
        $officerId = $request->query('officer_id');
        $query = LedgerTransaction::with('user')->where('type', 'OFFICER');
        
        if ($officerId && $officerId !== 'all') {
            $query->where('user_id', $officerId);
        }
        return response()->json($query->orderBy('id', 'desc')->get());
    }

    public function bankLedger(Request $request)
    {
        $bankId = $request->query('bank_id');
        $query = LedgerTransaction::with('user', 'bank')->where('type', 'BANK');
        
        if ($bankId) {
            $query->where('bank_id', $bankId);
        }
        return response()->json($query->orderBy('id', 'desc')->get());
    }

    public function submitToMain(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'officer_id' => 'required|exists:users,id'
        ]);

        $date = now()->toDateString();
        $user = \App\Models\User::find($request->officer_id);

        // 1. Credit (Out) from Officer
        LedgerTransaction::create([
            'date' => $date,
            'user_id' => $user->id,
            'account_type' => 'CASH SUBMIT',
            'description' => "Submitted to Main Branch",
            'amount' => $request->amount,
            'dr' => 0,
            'cr' => $request->amount,
            'type' => 'OFFICER'
        ]);

        // 2. Debit (In) to Main
        LedgerTransaction::create([
            'date' => $date,
            'user_id' => $user->id,
            'account_type' => 'CASH UPDATE',
            'description' => "Received from User: {$user->name}",
            'amount' => $request->amount,
            'dr' => $request->amount,
            'cr' => 0,
            'type' => 'MAIN'
        ]);

        return response()->json(['message' => 'Cash submitted successfully']);
    }

    public function depositToBank(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'bank_id' => 'required|exists:banks,id',
            'user_id' => 'required|exists:users,id' // Admin who deposits
        ]);

        $date = now()->toDateString();
        $bank = \App\Models\Bank::find($request->bank_id);

        // 1. Credit (Out) from Main
        LedgerTransaction::create([
            'date' => $date,
            'user_id' => $request->user_id,
            'account_type' => 'Cashbook Deposit',
            'description' => "Deposited to {$bank->name}",
            'amount' => $request->amount,
            'dr' => 0,
            'cr' => $request->amount,
            'type' => 'MAIN'
        ]);

        // 2. Debit (In) to Bank
        LedgerTransaction::create([
            'date' => $date,
            'user_id' => $request->user_id,
            'account_type' => 'Bank Deposit',
            'description' => "Branch Deposit",
            'amount' => $request->amount,
            'dr' => $request->amount,
            'cr' => 0,
            'type' => 'BANK',
            'bank_id' => $bank->id
        ]);

        return response()->json(['message' => 'Deposited successfully']);
    }
}
