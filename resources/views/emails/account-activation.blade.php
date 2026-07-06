<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Account Activated</title></head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px;">
<div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
<div style="background: linear-gradient(135deg, #1e3a5f, #2d5a8a); padding: 30px; text-align: center;">
<h1 style="color: #fff; margin: 0; font-size: 22px;">Account Activated!</h1>
</div>
<div style="padding: 30px;">
<p>Dear <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>
<p>Your account has been successfully activated after purchasing the minimum qualifying products.</p>
<p>You can now start building your team, earning commissions, and unlocking rewards. Log in to your dashboard to get started!</p>
<p style="text-align: center; margin-top: 30px;">
<a href="{{ config('app.url') }}/dashboard" style="background: #1e3a5f; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 5px;">Go to Dashboard</a>
</p>
</div>
<div style="background: #f8f8f8; padding: 15px; text-align: center; color: #888; font-size: 12px;">
<p>VSR MLM Network &copy; {{ date('Y') }}</p>
</div>
</div>
</body>
</html>
