<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0F6E56, #1D9E75);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            text-align: center;
            background: white;
            border-radius: 12px;
            padding: 60px 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        .error-icon {
            font-size: 80px;
            color: #0F6E56;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 3rem;
            font-weight: 700;
            color: #333;
            margin: 0;
        }
        p {
            color: #666;
            margin: 10px 0 30px 0;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h1>404</h1>
        <p>Page Not Found</p>
        <p style="color: #999; font-size: 0.95rem;">The page you're looking for doesn't exist.</p>
        <a href="/dashboard" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</body>
</html>
