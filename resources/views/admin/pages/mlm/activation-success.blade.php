<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Activated - VSR</title>
    <meta http-equiv="refresh" content="5;url={{ $userPanelUrl }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; display: flex; align-items: center; min-height: 100vh; }
        .card { border: none; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .icon-circle { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 2.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card shadow-sm text-center p-5">
                    <div class="icon-circle bg-success-subtle text-success mx-auto mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                        </svg>
                    </div>
                    <h3 class="fw-bold mt-2">Account Activated!</h3>
                    <p class="text-muted">Welcome, <strong>{{ $userName }}</strong>! Your account is now active.</p>
                    <div class="alert alert-info mt-3 py-2 small">
                        You can now login to your account and start using the platform.
                    </div>
                    <a href="{{ $userPanelUrl }}" class="btn btn-success btn-lg w-100 mt-3">
                        Go to User Panel
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
