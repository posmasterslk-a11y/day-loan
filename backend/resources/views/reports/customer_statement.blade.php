<!DOCTYPE html>
<html>
<head>
    <title>Customer Loan Statement</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1565c0; padding-bottom: 10px; }
        .title { font-size: 20px; font-weight: bold; color: #1565c0; margin-bottom: 5px; }
        .details-container { width: 100%; margin-bottom: 20px; }
        .details-col { width: 48%; display: inline-block; vertical-align: top; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        h3 { color: #1565c0; margin-bottom: 5px; font-size: 14px; border-bottom: 1px solid #ddd; padding-bottom: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Microfinance Hub</div>
        <div>Customer Loan Statement</div>
        <div>Date: {{ $date }}</div>
    </div>

    <div class="details-container">
        <div class="details-col">
            <strong>Customer Details:</strong><br>
            Name: {{ $loan->customer->full_name ?? '-' }}<br>
            NIC: {{ $loan->customer->nic ?? '-' }}<br>
            Phone: {{ $loan->customer->phone ?? '-' }}
        </div>
        <div class="details-col" style="text-align: right;">
            <strong>Loan Details:</strong><br>
            Loan No: {{ $loan->loan_number }}<br>
            Principal: LKR {{ number_format($loan->amount, 2) }}<br>
            Status: {{ $loan->status }}
        </div>
    </div>

    <h3>Payment History</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Receipt ID</th>
                <th>Collected By</th>
                <th class="text-right">Amount Paid (LKR)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loan->payments as $payment)
            <tr>
                <td>{{ $payment->payment_date }}</td>
                <td>RCPT-{{ str_pad($payment->id, 5, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $payment->officer->name ?? '-' }}</td>
                <td class="text-right">{{ number_format($payment->amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center;">No payments recorded yet.</td>
            </tr>
            @endforelse
            <tr>
                <td colspan="3" class="text-right" style="font-weight: bold;">Total Paid:</td>
                <td class="text-right" style="font-weight: bold;">{{ number_format($loan->payments->sum('amount'), 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
