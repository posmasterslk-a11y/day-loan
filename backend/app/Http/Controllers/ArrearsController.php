<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Http\Request;

class ArrearsController extends Controller
{
    public function index(Request $request)
    {
        // Auto-update Arrears daily if late
        \App\Models\LoanSchedule::where('due_date', '<', now()->toDateString())
            ->whereIn('status', ['Pending', 'Partial'])
            ->update(['status' => 'Missed']);

        $officerId = $request->query('officer_id');

        $query = Loan::with(['customer', 'product', 'schedules' => function ($q) {
            $q->where('status', 'Missed');
        }])
        ->whereHas('schedules', function ($q) {
            $q->where('status', 'Missed');
        });

        if ($officerId) {
            $query->whereHas('customer', function ($q) use ($officerId) {
                $q->where('officer_id', $officerId);
            });
        }

        $loans = $query->get()->map(function ($loan) {
            $arrearsAmount = $loan->schedules->sum('amount_due');
            
            // To be precise on partial payments, if status was Arrears, but it was partially paid,
            // we should subtract the paid amount. But since we didn't track partial amount tightly in schedule,
            // we will fetch Payments for these schedules.
            $paidAgainstArrears = \App\Models\Payment::whereIn('loan_schedule_id', $loan->schedules->pluck('id'))->sum('amount');
            $netArrears = $arrearsAmount - $paidAgainstArrears;

            return [
                'id' => $loan->id,
                'loan_number' => $loan->loan_number,
                'customer' => $loan->customer->full_name,
                'nic' => $loan->customer->nic,
                'phone' => $loan->customer->phone,
                'officer_id' => $loan->customer->officer_id,
                'total_arrears_amount' => $netArrears,
                'days_in_arrears' => $loan->schedules->count()
            ];
        });

        return response()->json($loans);
    }
}
