<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Team Member Activated</title></head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px;">
<div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
<div style="background: linear-gradient(135deg, #1e3a5f, #2d5a8a); padding: 30px; text-align: center;">
<h1 style="color: #fff; margin: 0; font-size: 22px;">Team Member Activated!</h1>
</div>
<div style="padding: 30px;">
<p>Dear <strong>{{ $sponsor->first_name }} {{ $sponsor->last_name }}</strong>,</p>
<p>Your sponsored team member has registered and activated their account.</p>
<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Name:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $activatedUser->first_name }} {{ $activatedUser->last_name }}</td></tr>
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Username:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $activatedUser->user_name }}</td></tr>
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Track ID:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $activatedUser->track_id }}</td></tr>
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Email:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $activatedUser->email }}</td></tr>
</table>
<p>Their placement is pending under your team binary. Please place them in your team to start team work.</p>
</div>
<div style="background: #f8f8f8; padding: 15px; text-align: center; color: #888; font-size: 12px;">
<p>VSR MLM Network &copy; {{ date('Y') }}</p>
</div>
</div>
</body>
</html>
