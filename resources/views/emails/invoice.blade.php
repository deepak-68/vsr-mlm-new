<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Invoice</title></head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px;">
<div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
<div style="background: linear-gradient(135deg, #1e3a5f, #2d5a8a); padding: 30px; text-align: center;">
<h1 style="color: #fff; margin: 0; font-size: 22px;">Invoice Generated</h1>
</div>
<div style="padding: 30px;">
<p>Dear <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>
<p>Your invoice <strong>{{ $invoice->invoice_number }}</strong> has been generated.</p>
<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Invoice #:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $invoice->invoice_number }}</td></tr>
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Date:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $invoice->invoice_date->format('d-m-Y') }}</td></tr>
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Total Amount:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">₹{{ number_format($invoice->total_amount, 2) }}</td></tr>
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Total CC:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ number_format($invoice->total_cc, 2) }}</td></tr>
</table>
<p>The invoice PDF is attached to this email.</p>
</div>
<div style="background: #f8f8f8; padding: 15px; text-align: center; color: #888; font-size: 12px;">
<p>VSR MLM Network &copy; {{ date('Y') }}</p>
</div>
</div>
</body>
</html>
