<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt — {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; max-width: 300px; margin: 0 auto; padding: 20px 10px; color: #000; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; margin: 2px 0; }
        .shop-name { font-size: 16px; font-weight: bold; margin-bottom: 4px; }
        .receipt-title { font-size: 14px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        td.right { text-align: right; }
        .total-row { font-size: 14px; font-weight: bold; }
        .badge { display: inline-block; padding: 2px 6px; border: 1px solid #000; font-size: 10px; text-transform: uppercase; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 16px;">
        <button onclick="window.print()" style="padding: 8px 24px; font-size: 14px; cursor: pointer; background: #4f46e5; color: #fff; border: none; border-radius: 4px;">
            Print Receipt
        </button>
        <button onclick="window.close()" style="padding: 8px 16px; font-size: 14px; cursor: pointer; background: #e5e7eb; border: none; border-radius: 4px; margin-left: 8px;">
            Close
        </button>
    </div>

    {{-- Shop Header --}}
    <div class="center">
        <div class="shop-name">{{ tenant('data')['shop_name'] ?? 'LaundryTrack' }}</div>
        <div class="receipt-title">LAUNDRY RECEIPT</div>
    </div>

    <div class="divider"></div>

    {{-- Order Info --}}
    <div class="row"><span>Order #:</span><span class="bold">{{ $order->order_number }}</span></div>
    <div class="row"><span>Date:</span><span>{{ $order->created_at->format('M d, Y h:i A') }}</span></div>
    <div class="row"><span>Customer:</span><span>{{ $order->customer->name }}</span></div>
    @if ($order->customer->phone)
        <div class="row"><span>Phone:</span><span>{{ $order->customer->phone }}</span></div>
    @endif
    @if ($order->service)
        <div class="row"><span>Service:</span><span>{{ $order->service->name }}</span></div>
    @endif
    @if ($order->weight)
        <div class="row"><span>Weight:</span><span>{{ $order->weight }} kg</span></div>
    @endif
    <div class="row"><span>Status:</span><span>{{ $order->status_label }}</span></div>
    @if ($order->due_date)
        <div class="row"><span>Due:</span><span>{{ $order->due_date->format('M d, Y') }}</span></div>
    @endif

    <div class="divider"></div>

    {{-- Items --}}
    @if ($order->items && count($order->items))
        <table>
            <tr class="bold">
                <td>Item</td>
                <td class="center">Qty</td>
                <td class="right">Price</td>
            </tr>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item['name'] ?? '—' }}</td>
                    <td class="center">{{ $item['qty'] ?? 1 }}</td>
                    <td class="right">{{ number_format(($item['qty'] ?? 1) * ($item['price'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </table>
        <div class="divider"></div>
    @endif

    {{-- Total --}}
    <div class="row total-row">
        <span>TOTAL</span>
        <span>₱{{ number_format($order->total_amount, 2) }}</span>
    </div>

    <div style="margin-top: 4px;">
        <div class="row">
            <span>Payment:</span>
            <span class="bold">{{ $order->isPaid() ? 'PAID' : 'UNPAID' }}</span>
        </div>
        @if ($order->isPaid() && $order->paid_at)
            <div class="row">
                <span>Paid at:</span>
                <span>{{ $order->paid_at->format('M d, Y h:i A') }}</span>
            </div>
        @endif
    </div>

    <div class="divider"></div>

    @if ($order->notes)
        <div style="margin-bottom: 8px;">
            <span class="bold">Notes:</span> {{ $order->notes }}
        </div>
        <div class="divider"></div>
    @endif

    <div class="center" style="margin-top: 8px;">
        <p>Thank you for your business!</p>
        <p style="margin-top: 4px; font-size: 10px; color: #666;">{{ now()->format('M d, Y h:i A') }}</p>
    </div>
</body>
</html>
