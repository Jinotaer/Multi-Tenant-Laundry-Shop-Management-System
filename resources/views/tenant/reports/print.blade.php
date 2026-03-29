@php
    use App\Models\Order;
    use Illuminate\Support\Facades\Storage;

    $statusLabels = Order::statusLabelsForPlan();
    $brandingEnabled = tenant()->hasFeature('custom_branding');
    $logoUrl = $brandingEnabled && tenant()->logo_path && Storage::disk('public')->exists(tenant()->logo_path)
        ? Storage::disk('public')->url(tenant()->logo_path)
        : null;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Business Report - {{ $periodLabel }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: #111827;
            background: #f8fafc;
        }

        .page {
            max-width: 1080px;
            margin: 0 auto;
            padding: 32px 24px 48px;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .button {
            border: 0;
            border-radius: 8px;
            padding: 10px 18px;
            font-size: 14px;
            cursor: pointer;
        }

        .button-primary {
            background: #111827;
            color: #ffffff;
        }

        .button-secondary {
            background: #e5e7eb;
            color: #111827;
        }

        .report-shell {
            background: #ffffff;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: flex-start;
            margin-bottom: 24px;
        }

        .brand {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            padding: 8px;
        }

        .eyebrow {
            margin: 0 0 8px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
        }

        h1 {
            margin: 0 0 6px;
            font-size: 30px;
            line-height: 1.1;
        }

        .muted {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        .meta {
            text-align: right;
            font-size: 14px;
            color: #4b5563;
        }

        .meta strong {
            display: block;
            color: #111827;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px;
            background: #f8fafc;
        }

        .card h2 {
            margin: 0 0 8px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #6b7280;
        }

        .card p {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }

        .card span {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: #6b7280;
        }

        .two-column {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .panel {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 20px;
        }

        .panel h3 {
            margin: 0 0 16px;
            font-size: 16px;
        }

        .list {
            display: grid;
            gap: 12px;
        }

        .list-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            font-size: 14px;
        }

        .bar-track {
            margin-top: 6px;
            height: 8px;
            border-radius: 999px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .bar {
            height: 100%;
            border-radius: 999px;
            background: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        th,
        td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }

        th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
        }

        .text-right {
            text-align: right;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .page {
                max-width: none;
                padding: 0;
            }

            .actions {
                display: none;
            }

            .report-shell {
                box-shadow: none;
                border-radius: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="actions">
            <button type="button" class="button button-primary" onclick="window.print()">Print / Save as PDF</button>
            <button type="button" class="button button-secondary" onclick="window.close()">Close</button>
        </div>

        <div class="report-shell">
            <div class="header">
                <div class="brand">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Shop Logo" class="logo">
                    @endif
                    <div>
                        <p class="eyebrow">Laundry Business Report</p>
                        <h1>{{ tenant('data')['shop_name'] ?? 'Laundry Shop' }}</h1>
                        <p class="muted">{{ $periodLabel }} performance summary with financial and order insights.</p>
                    </div>
                </div>
                <div class="meta">
                    <strong>{{ $periodLabel }}</strong>
                    <div>Generated {{ $generatedAt->format('M d, Y h:i A') }}</div>
                </div>
            </div>

            <div class="grid">
                <div class="card">
                    <h2>Total Revenue</h2>
                    <p>PHP {{ number_format($totalRevenue, 2) }}</p>
                    <span>Paid orders collected during the selected period.</span>
                </div>
                <div class="card">
                    <h2>Total Expenses</h2>
                    <p>PHP {{ number_format($totalExpenses, 2) }}</p>
                    <span>{{ tenant()->hasFeature('expense_tracking') ? 'Tracked operating costs included.' : 'Expense tracking is not enabled for this shop.' }}</span>
                </div>
                <div class="card">
                    <h2>Estimated Profit</h2>
                    <p>PHP {{ number_format($estimatedProfit, 2) }}</p>
                    <span>Revenue minus tracked expenses.</span>
                </div>
                <div class="card">
                    <h2>Total Orders</h2>
                    <p>{{ $totalOrders }}</p>
                    <span>{{ $paidOrders }} paid / {{ $unpaidOrders }} unpaid</span>
                </div>
                <div class="card">
                    <h2>Average Order Value</h2>
                    <p>PHP {{ number_format($averageOrderValue, 2) }}</p>
                    <span>Average value of paid orders.</span>
                </div>
                <div class="card">
                    <h2>Total Customers</h2>
                    <p>{{ $totalCustomers }}</p>
                    <span>Customers currently recorded in the tenant database.</span>
                </div>
            </div>

            <div class="two-column">
                <div class="panel">
                    <h3>Orders by Status</h3>
                    <div class="list">
                        @foreach ($statusLabels as $status => $label)
                            @php
                                $count = $ordersByStatus[$status] ?? 0;
                                $percentage = $totalOrders > 0 ? round(($count / $totalOrders) * 100) : 0;
                            @endphp
                            <div>
                                <div class="list-row">
                                    <span>{{ $label }}</span>
                                    <strong>{{ $count }}</strong>
                                </div>
                                <div class="bar-track">
                                    <div class="bar" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="panel">
                    <h3>Popular Services</h3>
                    @if ($popularServices->isEmpty())
                        <p class="muted">No service activity has been recorded yet.</p>
                    @else
                        <div class="list">
                            @foreach ($popularServices as $service)
                                <div class="list-row">
                                    <span>{{ $service->name }}</span>
                                    <strong>{{ $service->orders_count }} orders</strong>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="panel">
                <h3>Recent Orders</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $order->customer?->name ?? 'N/A' }}</td>
                                <td>{{ $order->service?->name ?? 'N/A' }}</td>
                                <td>{{ $statusLabels[$order->status] ?? ucfirst($order->status) }}</td>
                                <td class="text-right">PHP {{ number_format((float) $order->total_amount, 2) }}</td>
                                <td class="text-right">{{ $order->isPaid() ? 'Paid' : 'Unpaid' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">No recent orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
