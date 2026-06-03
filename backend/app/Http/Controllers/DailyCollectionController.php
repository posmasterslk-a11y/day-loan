<?php

namespace App\Http\Controllers;

use App\Models\LoanSchedule;
use App\Models\Payment;
use Illuminate\Http\Request;

class DailyCollectionController extends Controller
{
    public function index(Request $request)
    {
        $officerId = $request->query('officer_id');

        $query = LoanSchedule::with(['loan.customer', 'loan.product'])
            ->whereIn('status', ['Pending', 'Partial']);

        $search = $request->query('search');
        if ($search) {
            $query->whereHas('loan', function($q) use ($search) {
                $q->where('loan_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('full_name', 'like', "%{$search}%")
                         ->orWhere('nic', 'like', "%{$search}%");
                  });
            });
        } else {
            // Only show due today or earlier if no search
            $query->where('due_date', '<=', now()->toDateString());
        }

        if ($officerId) {
            $query->whereHas('loan.customer', function ($q) use ($officerId) {
                $q->where('officer_id', $officerId);
            });
        }

        return response()->json($query->orderBy('due_date', 'asc')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'loan_schedule_id' => 'required|exists:loan_schedules,id',
            'amount' => 'required|numeric|min:1',
            'officer_id' => 'required|exists:users,id'
        ]);

        $schedule = LoanSchedule::findOrFail($request->loan_schedule_id);
        $loan = $schedule->loan;

        $remainingCash = $request->amount;

        // Fetch all pending/partial schedules for this loan to cascade the payment
        $upcomingSchedules = LoanSchedule::where('loan_id', $loan->id)
            ->whereIn('status', ['Pending', 'Partial'])
            ->orderBy('due_date', 'asc')
            ->get();

        foreach ($upcomingSchedules as $sched) {
            if ($remainingCash <= 0) {
                break;
            }

            $paidAlready = Payment::where('loan_schedule_id', $sched->id)->sum('amount');
            $schedRemaining = $sched->amount_due - $paidAlready;

            if ($schedRemaining <= 0) {
                $sched->update(['status' => 'Paid']);
                continue;
            }

            $amountToApply = min($remainingCash, $schedRemaining);

            Payment::create([
                'loan_id' => $loan->id,
                'loan_schedule_id' => $sched->id,
                'officer_id' => $request->officer_id,
                'amount' => $amountToApply,
                'payment_date' => now()->toDateString(),
                'status' => 'Completed'
            ]);

            $remainingCash -= $amountToApply;

            $newPaidAlready = $paidAlready + $amountToApply;
            $status = ($newPaidAlready >= $sched->amount_due) ? 'Paid' : 'Partial';
            $sched->update(['status' => $status]);
        }

        // If there is still remaining cash (overpayment beyond the entire loan), apply it to the last schedule to avoid losing the record
        if ($remainingCash > 0 && $upcomingSchedules->isNotEmpty()) {
            $lastSched = $upcomingSchedules->last();
            Payment::create([
                'loan_id' => $loan->id,
                'loan_schedule_id' => $lastSched->id,
                'officer_id' => $request->officer_id,
                'amount' => $remainingCash,
                'payment_date' => now()->toDateString(),
                'status' => 'Completed'
            ]);
            $lastSched->update(['status' => 'Paid']);
        }

        // Ledger Entry for the full collected amount
        $user = \App\Models\User::find($request->officer_id);
        $type = ($user && $user->role_id != 3) ? 'MAIN' : 'OFFICER';

        \App\Models\LedgerTransaction::create([
            'date' => now()->toDateString(),
            'user_id' => $request->officer_id,
            'account_type' => 'COLLECTION',
            'description' => "Collected for Loan: {$loan->loan_number}",
            'amount' => $request->amount,
            'dr' => $request->amount,
            'cr' => 0,
            'type' => $type
        ]);

        $loan->total_payable = max(0, $loan->total_payable - $request->amount);
        if ($loan->total_payable == 0) {
            $loan->status = 'Settled';
        }
        $loan->save();

        return response()->json([
            'message' => 'Payment collected successfully and schedules updated!',
            'loan' => $loan
        ]);
    }
}
