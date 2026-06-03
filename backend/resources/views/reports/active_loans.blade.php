<!DOCTYPE html>
<html>
<head>
    <title>Active Loans Summary</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2e7d32; padding-bottom: 10px; }
        .title { font-size: 20px; font-weight: bold; color: #2e7d32; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background-color: #e8f5e9; }
        .badge { padding: 3px 6px; border-radius: 4px; font-size: 10px; background: #e0e0e0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Microfinance Hub</div>
        <div>Active Loans Summary</div>
        <div>Generated on: {{ $date }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Loan No.</th>
                <th>Customer</th>
                <th>Issued Date</th>
                <th>Status</th>
                <th class="text-right">Principal (LKR)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loans as $index => $loan)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $loan->loan_number }}</td>
                <td>{{ $loan->customer->full_name ?? '-' }}</td>
                <td>{{ $loan->created_at->format('Y-m-d') }}</td>
                <td><span class="badge">{{ $loan->status }}</span></td>
                <td class="text-right">{{ number_format($loan->amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center;">No active loans found.</td>
            </tr>
            @endforelse
            
            <tr class="total-row">
                <td colspan="5" class="text-right">Total Principal Issued:</td>
                <td class="text-right">{{ number_format($totalPrincipal, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
