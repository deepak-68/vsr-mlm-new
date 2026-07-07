<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activation Failed - VSR</title>
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
                    <div class="icon-circle bg-danger-subtle text-danger mx-auto mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
                        </svg>
                    </div>
                    <h3 class="fw-bold mt-2">Activation Failed</h3>
                    <p class="text-muted">{{ $message ?? 'The activation link is invalid or has expired.' }}</p>
                    <a href="{{ $userPanelUrl }}" class="btn btn-secondary btn-lg w-100 mt-3">
                        Go to User Panel
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
