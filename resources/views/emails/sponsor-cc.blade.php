<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>CC Points Credited</title></head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px;">
<div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
<div style="background: linear-gradient(135deg, #1e3a5f, #2d5a8a); padding: 30px; text-align: center;">
<h1 style="color: #fff; margin: 0; font-size: 22px;">CC Points Credited</h1>
</div>
<div style="padding: 30px;">
<p>Dear <strong>{{ $sponsor->first_name }} {{ $sponsor->last_name }}</strong>,</p>
<p><strong>{{ $recipient->first_name }} {{ $recipient->last_name }}</strong> (Track ID: {{ $recipient->track_id }}) has made a purchase and you have earned CC points as their sponsor.</p>
<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Order #:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $order->id }}</td></tr>
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Recipient:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $recipient->first_name }} {{ $recipient->last_name }} ({{ $recipient->track_id }})</td></tr>
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>CC Points Credited:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>{{ number_format($ccPoints, 2) }}</strong></td></tr>
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Order Date:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $order->order_date ? $order->order_date->format('d-m-Y') : $order->created_at->format('d-m-Y') }}</td></tr>
</table>
<p>Keep growing your team to earn more!</p>
</div>
<div style="background: #f8f8f8; padding: 15px; text-align: center; color: #888; font-size: 12px;">
<p>VSR MLM Network &copy; {{ date('Y') }}</p>
</div>
</div>
</body>
</html>
