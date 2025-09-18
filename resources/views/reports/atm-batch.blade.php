<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ATM Batch Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            flex-direction: row;
        }
        .header-image {
            max-height: 50px;
            max-width: 100px;
            display: block;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        .header h2 {
            margin: 5px 0 0 0;
            font-size: 14px;
            font-weight: normal;
        }
        .report-info {
            margin-bottom: 20px;
        }
        .report-info p {
            margin: 2px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        .amount {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-left {
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <div class="header-left">
                @if($picture1Base64)
                    <img src="{{ $picture1Base64 }}" alt="Logo Left" class="header-image">
                @endif
            </div>
            <div class="header-center">
                <h1>ATM BATCH REPORT</h1>
                <h2>{{ $branchName }}</h2>
            </div>
            <div class="header-right">
                @if($picture2Base64)
                    <img src="{{ $picture2Base64 }}" alt="Logo Right" class="header-image">
                @endif
            </div>
        </div>
    </div>

    <div class="report-info">
        <p><strong>Date:</strong> {{ $allDates ? 'All Dates' : $date }}</p>
        <p><strong>Generated:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 20%;">Member</th>
                <th style="width: 12%;" class="amount">Amount</th>
                <th style="width: 12%;" class="amount">POS CHARGE</th>
                <th style="width: 12%;" class="amount">CA (Pinoy Coop)</th>
                <th style="width: 12%;" class="amount">LOANS</th>
                <th style="width: 12%;" class="amount">Others</th>
                <th style="width: 12%;" class="amount">Net Amount Due</th>
                <th style="width: 20%;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportData as $row)
                <tr>
                    <td class="text-left">{{ $row['member'] }}</td>
                    <td class="amount">P{{ number_format($row['amount'], 2) }}</td>
                    <td class="amount">{{ $row['pos_charge'] > 0 ? 'P' . number_format($row['pos_charge'], 2) : '' }}</td>
                    <td class="amount">P{{ number_format($row['ca_amount'], 2) }}</td>
                    <td class="amount">P{{ number_format($row['loans'], 2) }}</td>
                    <td class="amount">P{{ number_format($row['others'], 2) }}</td>
                    <td class="amount">P{{ number_format($row['net_amount_due'], 2) }}</td>
                    <td class="text-left">{{ $row['remarks'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No ATM payments found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Report generated on {{ now()->format('F d, Y \a\t g:i A') }}</p>
        <p>ATM Batch Report - {{ $branchName }}</p>
    </div>
</body>
</html>
