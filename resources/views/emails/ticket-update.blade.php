<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Ticket Update</title></head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px;">
<div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
<div style="background: linear-gradient(135deg, #1e3a5f, #2d5a8a); padding: 30px; text-align: center;">
<h1 style="color: #fff; margin: 0; font-size: 22px;">Ticket Updated</h1>
</div>
<div style="padding: 30px;">
<p>Dear <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>
<p>Your support ticket <strong>#{{ $ticketNo }}</strong> has been updated:</p>
<div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #1e3a5f; margin: 15px 0;">
<p style="margin: 0;">{{ $update }}</p>
</div>
<p>Log in to your account to view the full conversation.</p>
</div>
<div style="background: #f8f8f8; padding: 15px; text-align: center; color: #888; font-size: 12px;">
<p>VSR MLM Network &copy; {{ date('Y') }}</p>
</div>
</div>
</body>
</html>
