<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function dailyCollection(Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        
        $payments = Payment::with(['loan.customer', 'officer'])
            ->where('payment_date', $date)
            ->orderBy('created_at', 'desc')
            ->get();
            
        $totalCollected = $payments->sum('amount');
        
        $pdf = Pdf::loadView('reports.daily_collection', [
            'date' => $date,
            'payments' => $payments,
            'totalCollected' => $totalCollected
        ]);
        
        return $pdf->download("daily_collection_{$date}.pdf");
    }

    public function arrears()
    {
        // Loans that have pending or partial schedules past due
        $loans = Loan::with(['customer', 'schedules' => function ($query) {
            $query->where('due_date', '<', now()->toDateString())
                  ->whereIn('status', ['Pending', 'Partial']);
        }])
        ->where('status', '!=', 'Completed')
        ->get()
        ->filter(function ($loan) {
            return $loan->schedules->isNotEmpty();
        });

        $pdf = Pdf::loadView('reports.arrears', [
            'loans' => $loans,
            'date' => now()->toDateString()
        ]);
        
        return $pdf->download("arrears_report_" . now()->toDateString() . ".pdf");
    }

    public function activeLoans()
    {
        $loans = Loan::with(['customer'])
            ->where('status', '!=', 'Completed')
            ->orderBy('created_at', 'desc')
            ->get();
            
        $totalPrincipal = $loans->sum('amount');
            
        $pdf = Pdf::loadView('reports.active_loans', [
            'loans' => $loans,
            'totalPrincipal' => $totalPrincipal,
            'date' => now()->toDateString()
        ]);
        
        return $pdf->download("active_loans_" . now()->toDateString() . ".pdf");
    }

    public function customerStatement($id)
    {
        $loan = Loan::with(['customer', 'schedules', 'payments'])->findOrFail($id);
        
        $pdf = Pdf::loadView('reports.customer_statement', [
            'loan' => $loan,
            'date' => now()->toDateString()
        ]);
        
        return $pdf->download("statement_" . $loan->loan_number . ".pdf");
    }
}
