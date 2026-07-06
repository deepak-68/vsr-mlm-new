<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>New Team Member</title></head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px;">
<div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
<div style="background: linear-gradient(135deg, #1e3a5f, #2d5a8a); padding: 30px; text-align: center;">
<h1 style="color: #fff; margin: 0; font-size: 22px;">New Team Member Joined!</h1>
</div>
<div style="padding: 30px;">
<p>Dear <strong>{{ $sponsor->first_name }} {{ $sponsor->last_name }}</strong>,</p>
<p>A new member has joined under your network:</p>
<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Name:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $newUser->first_name }} {{ $newUser->last_name }}</td></tr>
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Username:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $newUser->user_name }}</td></tr>
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Track ID:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $newUser->track_id }}</td></tr>
<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Email:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $newUser->email }}</td></tr>
</table>
<p>Support your new team member to help them get started!</p>
</div>
<div style="background: #f8f8f8; padding: 15px; text-align: center; color: #888; font-size: 12px;">
<p>VSR MLM Network &copy; {{ date('Y') }}</p>
</div>
</div>
</body>
</html>
