<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 24px; color: #1e3a5f; }
        .header p { margin: 5px 0; color: #666; }
        .details { margin-bottom: 20px; }
        .details table { width: 100%; }
        .details td { vertical-align: top; padding: 5px 0; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .meta td { padding: 4px 8px; }
        .meta .label { font-weight: bold; width: 150px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items th { background: #1e3a5f; color: #fff; padding: 8px; text-align: left; }
        table.items td { padding: 8px; border-bottom: 1px solid #ddd; }
        table.items tr:nth-child(even) { background: #f9f9f9; }
        .total-row td { font-weight: bold; font-size: 14px; padding-top: 12px; }
        .footer { text-align: center; margin-top: 40px; color: #999; font-size: 10px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 10px; }
        .badge-success { background: #28a745; color: #fff; }
    </style>
</head>
<body>
    <div class="header">
        <h1>TAX INVOICE</h1>
        <p>VSR MLM Pvt. Ltd.</p>
    </div>

    <table class="details">
        <tr>
            <td width="50%">
                <strong>Invoice To:</strong><br>
                {{ $order->user->first_name }} {{ $order->user->last_name }}<br>
                {{ $order->user->email }}<br>
                Track ID: {{ $order->user->track_id }}
            </td>
            <td width="50%" style="text-align: right;">
                <strong>Invoice #:</strong> {{ $invoice->invoice_number }}<br>
                <strong>Date:</strong> {{ $invoice->invoice_date->format('d-m-Y') }}<br>
                <strong>Status:</strong> <span class="badge badge-success">{{ $invoice->status }}</span>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Price</th>
                <th>Qty</th>
                <th>CC Points</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->product->name ?? 'Product #' . $item->product_id }}</td>
                <td>₹{{ number_format($item->price, 2) }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->cc_points, 2) }}</td>
                <td>₹{{ number_format($item->price * $item->quantity, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4"></td>
                <td>Total CC:</td>
                <td>{{ number_format($order->total_cc_points, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="4"></td>
                <td>Total Amount:</td>
                <td>₹{{ number_format($order->total_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>This is a computer-generated invoice. No signature is required.</p>
        <p>VSR MLM Pvt. Ltd. &copy; {{ date('Y') }}</p>
    </div>
</body>
</html>
