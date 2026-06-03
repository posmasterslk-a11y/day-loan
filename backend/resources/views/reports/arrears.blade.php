<!DOCTYPE html>
<html>
<head>
    <title>Arrears & Defaulters Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #c62828; padding-bottom: 10px; }
        .title { font-size: 20px; font-weight: bold; color: #c62828; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .text-danger { color: #c62828; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Microfinance Hub</div>
        <div>Arrears & Defaulters Report</div>
        <div>Generated on: {{ $date }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Loan No.</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Total Arrears (LKR)</th>
                <th>Missed Installments</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loans as $loan)
                @php
                    $totalArrears = $loan->schedules->sum('amount_due') - $loan->schedules->sum('amount_paid');
                    $missedCount = $loan->schedules->count();
                @endphp
            <tr>
                <td>{{ $loan->loan_number }}</td>
                <td>{{ $loan->customer->full_name ?? '-' }}</td>
                <td>{{ $loan->customer->phone ?? '-' }}</td>
                <td class="text-right text-danger">{{ number_format($totalArrears, 2) }}</td>
                <td class="text-right">{{ $missedCount }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center;">No loans currently in arrears! Excellent!</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
