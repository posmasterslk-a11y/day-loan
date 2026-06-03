<?php

namespace App\Http\Controllers;

use App\Models\LedgerAccount;
use App\Models\LedgerTransaction;
use Illuminate\Http\Request;

class PaymentReceiptController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ledger_account_id' => 'required|exists:ledger_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'source' => 'required|in:MAIN,BANK',
            'bank_id' => 'required_if:source,BANK|nullable|exists:banks,id',
            'date' => 'required|date'
        ]);

        $ledgerAccount = LedgerAccount::findOrFail($validated['ledger_account_id']);
        
        // If type is DR (Expense), it means cash is going OUT (Crediting Cashbook, DR the Ledger Account).
        // Wait, LedgerTransaction stores DR and CR.
        // For our LedgerTransaction table, DR = Cash In, CR = Cash Out.
        // So if Ledger Account is 'DR', it's an expense, so it should be a CR in our LedgerTransaction.
        // If Ledger Account is 'CR', it's an income, so it should be a DR in our LedgerTransaction.
        
        $dr = 0;
        $cr = 0;
        
        // As per user convention: DR = Income/Cash In, CR = Expense/Cash Out
        if ($ledgerAccount->type === 'DR') {
            $dr = $validated['amount']; // Income -> Cash In
        } else {
            $cr = $validated['amount']; // Expense -> Cash Out
        }

        $transaction = LedgerTransaction::create([
            'date' => $validated['date'],
            'user_id' => auth()->id() ?? 1, // fallback for testing
            'account_type' => 'GENERAL', // generic account type
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'dr' => $dr,
            'cr' => $cr,
            'type' => $validated['source'], // MAIN or BANK
            'bank_id' => $validated['bank_id'] ?? null,
            'ledger_account_id' => $ledgerAccount->id
        ]);

        return response()->json([
            'message' => 'Transaction saved successfully',
            'transaction' => $transaction
        ], 201);
    }

    public function getGeneralLedger(Request $request)
    {
        $query = LedgerTransaction::with('ledgerAccount')
                    ->whereNotNull('ledger_account_id')
                    ->orderBy('date', 'desc')
                    ->orderBy('id', 'desc');

        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('date', [$request->from, $request->to]);
        }

        if ($request->has('ledger_account_id') && $request->ledger_account_id !== 'all') {
            $query->where('ledger_account_id', $request->ledger_account_id);
        }

        $transactions = $query->get();

        $totalDr = $transactions->sum('dr');
        $totalCr = $transactions->sum('cr');
        $balance = $totalDr - $totalCr; // Positive balance means net inflow

        return response()->json([
            'transactions' => $transactions,
            'summary' => [
                'total_dr' => $totalDr,
                'total_cr' => $totalCr,
                'balance' => $balance
            ]
        ]);
    }
}
