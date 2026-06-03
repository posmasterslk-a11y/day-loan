<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\Payment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats()
    {
        $totalCustomers = Customer::count();
        $totalActiveLoans = Loan::where('status', '!=', 'Completed')->count();
        $totalLoanValue = Loan::sum('amount');
        
        $todayExpected = LoanSchedule::where('due_date', now()->toDateString())
            ->whereIn('status', ['Pending', 'Partial'])
            ->sum('amount_due');

        $todayCollected = Payment::where('payment_date', now()->toDateString())->sum('amount');
        
        $loansThisMonthData = Loan::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->get();

        $loansThisMonth = $loansThisMonthData->count();
        $loanValueThisMonth = $loansThisMonthData->sum('amount');
        $profitThisMonth = $loansThisMonthData->sum(function($loan) {
            return $loan->amount * ($loan->interest_rate / 100);
        });

        $recentPayments = Payment::with(['loan.customer', 'officer'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'customers' => $totalCustomers,
            'active_loans' => $totalActiveLoans,
            'loan_value' => $totalLoanValue,
            'today_expected' => $todayExpected,
            'today_collected' => $todayCollected,
            'loans_this_month' => $loansThisMonth,
            'loan_value_this_month' => $loanValueThisMonth,
            'profit_this_month' => $profitThisMonth,
            'recent_payments' => $recentPayments
        ]);
    }

    public function chart(Request $request)
    {
        $start = $request->query('start', now()->subDays(14)->toDateString());
        $end = $request->query('end', now()->toDateString());
        
        $payments = Payment::whereBetween('payment_date', [$start, $end])
            ->selectRaw('payment_date as date, sum(amount) as amount')
            ->groupBy('payment_date')
            ->orderBy('payment_date', 'asc')
            ->get();
            
        return response()->json($payments);
    }
}
