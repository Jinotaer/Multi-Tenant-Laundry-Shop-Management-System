<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.5;
            padding: 40px;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 20px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #4f46e5;
        }
        .invoice-number {
            color: #666;
            margin-top: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-issued {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .status-overdue {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-draft {
            background-color: #f3f4f6;
            color: #4b5563;
        }
        .addresses {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .address-block {
            width: 45%;
        }
        .address-label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .address-name {
            font-weight: 600;
            color: #111;
        }
        .address-detail {
            color: #666;
            font-size: 13px;
        }
        .dates-box {
            background-color: #f9fafb;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            gap: 40px;
        }
        .date-item label {
            font-size: 12px;
            color: #666;
            display: block;
        }
        .date-item span {
            font-weight: 600;
            color: #111;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th {
            text-align: left;
            padding: 12px 0;
            border-bottom: 2px solid #e5e7eb;
            font-size: 12px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        th:last-child {
            text-align: right;
        }
        td {
            padding: 16px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        td:last-child {
            text-align: right;
        }
        .item-name {
            font-weight: 600;
            color: #111;
        }
        .item-detail {
            font-size: 13px;
            color: #666;
        }
        .totals {
            margin-left: auto;
            width: 250px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        .total-row.final {
            border-top: 2px solid #e5e7eb;
            margin-top: 8px;
            padding-top: 12px;
            font-weight: bold;
            font-size: 16px;
        }
        .total-label {
            color: #666;
        }
        .total-value {
            color: #111;
        }
        .notes {
            margin-top: 40px;
            padding: 15px 20px;
            background-color: #f9fafb;
            border-radius: 8px;
        }
        .notes-label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .footer {
            margin-top: 60px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
        @media print {
            body {
                padding: 20px;
            }
            .invoice-header {
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div>
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-number">{{ $invoice->invoice_number }}</div>
        </div>
        <div>
            <span class="status-badge status-{{ $invoice->status }}">{{ $invoice->status_label }}</span>
        </div>
    </div>

    <div class="addresses">
        <div class="address-block">
            <div class="address-label">From</div>
            <div class="address-name">LaundryPro SaaS</div>
            <div class="address-detail">admin@laundrypro.com</div>
        </div>
        <div class="address-block">
            <div class="address-label">Bill To</div>
            <div class="address-name">{{ $invoice->billing_name }}</div>
            <div class="address-detail">{{ $invoice->billing_email }}</div>
            @if ($invoice->billing_address)
                <div class="address-detail">{{ $invoice->billing_address }}</div>
            @endif
            <div class="address-detail">Shop: {{ $invoice->tenant->registration->shop_name ?? $invoice->tenant_id }}</div>
        </div>
    </div>

    <div class="dates-box">
        <div class="date-item">
            <label>Issue Date</label>
            <span>{{ $invoice->issue_date->format('M d, Y') }}</span>
        </div>
        <div class="date-item">
            <label>Due Date</label>
            <span>{{ $invoice->due_date->format('M d, Y') }}</span>
        </div>
        @if ($invoice->paid_at)
            <div class="date-item">
                <label>Paid Date</label>
                <span style="color: #166534;">{{ $invoice->paid_at->format('M d, Y') }}</span>
            </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="item-name">{{ $invoice->subscriptionPlan?->name ?? 'Subscription' }} Plan</div>
                    @if ($invoice->period_start && $invoice->period_end)
                        <div class="item-detail">Period: {{ $invoice->period_start->format('M d, Y') }} - {{ $invoice->period_end->format('M d, Y') }}</div>
                    @endif
                    @if ($invoice->subscriptionPlan)
                        <div class="item-detail">Billing: {{ ucfirst($invoice->subscriptionPlan->billing_cycle) }}</div>
                    @endif
                </td>
                <td>{{ $invoice->formatted_subtotal }}</td>
            </tr>
        </tbody>
    </table>

    <div class="totals">
        <div class="total-row">
            <span class="total-label">Subtotal</span>
            <span class="total-value">{{ $invoice->formatted_subtotal }}</span>
        </div>
        @if ($invoice->tax_amount > 0)
            <div class="total-row">
                <span class="total-label">Tax ({{ $invoice->tax_rate }}%)</span>
                <span class="total-value">₱{{ number_format($invoice->tax_amount, 2) }}</span>
            </div>
        @endif
        <div class="total-row final">
            <span>Total</span>
            <span>{{ $invoice->formatted_total }}</span>
        </div>
    </div>

    @if ($invoice->notes)
        <div class="notes">
            <div class="notes-label">Notes</div>
            <p>{{ $invoice->notes }}</p>
        </div>
    @endif

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>Generated on {{ now()->format('M d, Y H:i') }}</p>
    </div>
</body>
</html>
