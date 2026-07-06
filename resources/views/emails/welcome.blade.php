<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Welcome to VSR MLM</title></head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px;">
<div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
<div style="background: linear-gradient(135deg, #1e3a5f, #2d5a8a); padding: 30px; text-align: center;">
<h1 style="color: #fff; margin: 0; font-size: 24px;">Welcome to VSR MLM Network!</h1>
</div>
<div style="padding: 30px;">
<p>Dear <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>
<p>Thank you for joining VSR MLM Network. We are excited to have you on board!</p>
<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Username:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $user->user_name }}</td></tr>
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Track ID:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $user->track_id }}</td></tr>
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Email:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $user->email }}</td></tr>
</table>
<p>Start exploring our products and building your network today!</p>
<p style="text-align: center; margin-top: 30px;">
<a href="{{ config('app.url') }}" style="background: #1e3a5f; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 5px;">Go to Dashboard</a>
</p>
</div>
<div style="background: #f8f8f8; padding: 15px; text-align: center; color: #888; font-size: 12px;">
<p>VSR MLM Network &copy; {{ date('Y') }}. All rights reserved.</p>
</div>
</div>
</body>
</html>
