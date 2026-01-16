<html dir="ltr">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 14px;
            color: #333;
            direction: ltr;
        }
        .header {
            width: 100%;
            margin-bottom: 20px;
        }
        .header td {
            vertical-align: top;
        }
        .invoice-details {
            text-align: right;
        }
        .invoice-details-inner {
            display: inline-block;
            text-align: left;
            direction: ltr;
        }
        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        /* Table Styling */
        table.tasks {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 10px; /* Reduced font size */
        }
        table.tasks th, table.tasks td {
            border: 1px solid #ddd;
            padding: 4px; /* Reduced padding */
            text-align: left;
            vertical-align: top;
        }
        table.tasks th {
            background-color: #f4f4f4;
            font-weight: bold;
            white-space: nowrap;
        }
        /* Zebra Striping */
        table.tasks tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        /* Column Widths & Wrapping */
        table.tasks td {
            white-space: nowrap; /* Default to no-wrap */
        }
        table.tasks td.address-col {
            white-space: normal; /* Allow wrapping for address */
            min-width: 150px;
        }
        table.tasks td.customer-col {
             white-space: normal; /* Allow wrapping for long names too, safely */
             max-width: 120px;
        }

        .total-section {
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #777;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            color: white;
            background-color: #777; 
        }
        .badge-canceled { background-color: #ef4444; }
    </style>
</head>
<body>

    <table class="header">
        <tr>
            <td>
                <img src="{{ public_path('img/logo.png') }}" alt="Wire3 Logo" style="max-height: 80px;">
                <div style="font-size: 16px; font-weight: bold; margin-top: 5px;">Technician Services</div>
            </td>
            <td class="invoice-details">
                <div class="invoice-details-inner">
                    <div class="invoice-title">Weekly Service Invoice</div>
                    <div># {{ $invoice->invoice_number }}</div>
                    <br>
                    <div><strong>Invoice Start:</strong> {{ $invoice->period_start->format('M d, Y') }}</div>
                    <div><strong>Invoice End:</strong> {{ $invoice->period_end->format('M d, Y') }}</div>
                    <div><strong>Week #{{ $invoice->week_number }}</strong></div>
                    <div style="margin-top: 5px;"><strong>Due Date:</strong> Upon Receipt</div>
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-bottom: 20px;">
        <strong>Bill To:</strong><br>
        Wire 3 LLC
    </div>

    <table class="tasks">
        <thead>
            <tr>
                <th>Date</th>
                <th>CID</th>
                <th>Customer Name</th>
                <th>Address</th>
                <th>Phone No</th>
                <th>Job Type</th>
                <th style="text-align: right;">Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->tasks as $task)
                <tr>
                    <td>{{ $task->completion_date ? $task->completion_date->format('M d') : '-' }}</td>
                    <td>{{ $task->customer->wire3_cid ?? 'N/A' }}</td>
                    <td class="customer-col">{{ $task->customer?->name ?? 'N/A' }}</td>
                    <td class="address-col">{{ $task->customer?->address ?? 'N/A' }}</td>
                    <td>{{ $task->customer?->phone ?? 'N/A' }}</td>
                    <td>
                        {{ $task->task_type->getLabel() ?? $task->task_type }}
                        @if($task->status === \App\Enums\TaskStatus::Cancelled)
                            <span class="badge badge-canceled">Cancelled</span>
                        @endif
                    </td>
                    <td style="text-align: right;">${{ number_format($task->company_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" style="text-align: right; font-weight: bold;">TOTAL</td>
                <td style="text-align: right; font-weight: bold;">${{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="total-section">
        Total Payable: ${{ number_format($invoice->total_amount, 2) }}
    </div>

    <div class="footer">
        Generated by XConnect ISP Software
    </div>

</body>
</html>
