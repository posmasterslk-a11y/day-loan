<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\LoanSchedule;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LoanController extends Controller
{
    public function index()
    {
        return response()->json(Loan::with(['customer', 'product', 'guarantor'])->orderBy('id', 'desc')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'loan_product_id' => 'required|exists:loan_products,id',
            'amount' => 'required|numeric|min:0',
            'guarantor_id' => 'nullable|exists:guarantors,id',
        ]);

        $product = LoanProduct::findOrFail($data['loan_product_id']);

        if ($data['amount'] < $product->min_amount || $data['amount'] > $product->max_amount) {
            return response()->json(['message' => "Amount must be between {$product->min_amount} and {$product->max_amount}"], 422);
        }

        $interestAmount = $data['amount'] * ($product->interest_rate / 100);
        $totalPayable = $data['amount'] + $interestAmount;
        $dailyInstallment = $totalPayable / $product->duration_days;

        $loan = Loan::create([
            'loan_number' => 'LN-' . time(),
            'customer_id' => $data['customer_id'],
            'loan_product_id' => $product->id,
            'guarantor_id' => $data['guarantor_id'] ?? null,
            'amount' => $data['amount'],
            'interest_rate' => $product->interest_rate,
            'start_date' => Carbon::today(),
            'term_days' => $product->duration_days,
            'total_payable' => $totalPayable,
            'daily_installment' => $dailyInstallment,
            'status' => 'Pending',
        ]);

        // Generate schedules
        for ($i = 1; $i <= $product->duration_days; $i++) {
            LoanSchedule::create([
                'loan_id' => $loan->id,
                'due_date' => Carbon::today()->addDays($i),
                'amount_due' => $dailyInstallment,
                'status' => 'Pending',
            ]);
        }

        return response()->json(['message' => 'Loan created and schedule generated successfully!', 'loan' => $loan->load('customer')], 201);
    }

    public function approve($id)
    {
        $loan = Loan::findOrFail($id);
        
        if ($loan->status !== 'Pending') {
            return response()->json(['message' => 'Only pending loans can be approved'], 400);
        }

        $loan->update(['status' => 'Approved']);
        
        // Also update the schedules to active if needed, but usually schedules just wait for payment
        // We can just leave schedules as Pending since they are waiting for payment.

        return response()->json(['message' => 'Loan approved successfully', 'loan' => $loan]);
    }
}
