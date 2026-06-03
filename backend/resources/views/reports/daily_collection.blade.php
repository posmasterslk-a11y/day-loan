<!DOCTYPE html>
<html>
<head>
    <title>Daily Collection Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2e7d32; padding-bottom: 10px; }
        .title { font-size: 20px; font-weight: bold; color: #2e7d32; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background-color: #e8f5e9; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Microfinance Hub</div>
        <div>Daily Collection Report</div>
        <div>Date: {{ $date }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Loan No.</th>
                <th>Customer</th>
                <th>Officer</th>
                <th>Time</th>
                <th class="text-right">Amount (LKR)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $index => $payment)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $payment->loan->loan_number ?? '-' }}</td>
                <td>{{ $payment->loan->customer->full_name ?? '-' }}</td>
                <td>{{ $payment->officer->name ?? '-' }}</td>
                <td>{{ $payment->created_at->format('H:i') }}</td>
                <td class="text-right">{{ number_format($payment->amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center;">No collections found for this date.</td>
            </tr>
            @endforelse
            
            <tr class="total-row">
                <td colspan="5" class="text-right">Total Collected:</td>
                <td class="text-right">{{ number_format($totalCollected, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
