<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Binary Position Assigned</title></head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px;">
<div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
<div style="background: linear-gradient(135deg, #1e3a5f, #2d5a8a); padding: 30px; text-align: center;">
<h1 style="color: #fff; margin: 0; font-size: 22px;">Binary Position Assigned</h1>
</div>
<div style="padding: 30px;">
<p>Dear <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>
<p>You have been placed in the binary tree under <strong>{{ $parentName ?? 'the system' }}</strong> on the <strong>{{ ucfirst($position) }}</strong> side.</p>
<p>Track ID: <strong>{{ $user->track_id }}</strong></p>
<p>Start building your team and earning commissions!</p>
</div>
<div style="background: #f8f8f8; padding: 15px; text-align: center; color: #888; font-size: 12px;">
<p>VSR MLM Network &copy; {{ date('Y') }}</p>
</div>
</div>
</body>
</html>
