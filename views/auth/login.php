<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dr. Feelgood</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
     <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/png" href="/assets/logo/favicon-32.png">
    <link rel="apple-touch-icon" href="/assets/logo/apple-touch-icon.png">
    <meta name="theme-color" content="#0d6efd">
   
   <style>
        body {
            background: linear-gradient(135deg, #0F6E56, #1D9E75);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header .logo {
            margin-bottom: 10px;
        }

        .login-header .logo img {
            height: 96px;
            width: auto;
        }

        .login-header h1 {
            font-size: 28px;
            color: #333;
            font-weight: 700;
            margin: 0;
        }

        .login-header p {
            color: #999;
            margin: 5px 0 0 0;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: #0F6E56;
            box-shadow: 0 0 0 0.2rem rgba(15, 110, 86, 0.25);
        }

        .form-control::placeholder {
            color: #ccc;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #0F6E56, #1D9E75);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s ease;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(15, 110, 86, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 6px;
            border: none;
            margin-bottom: 20px;
        }

        .remember-me {
            font-size: 14px;
        }

        .form-check-input:checked {
            background-color: #0F6E56;
            border-color: #0F6E56;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .spinner {
            display: none;
        }

        .spinner.show {
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <img src="/assets/logo/app-logo.png" alt="Dr. Feelgood">
            </div>
            <h1>Dr. Feelgood`s</h1>
            <p>Clinic Management System</p>
        </div>

        <?php if (isset($_GET['expired'])): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-hourglass-end"></i> Your session has expired. Please login again.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="/login">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <div class="form-check remember-me" style="margin-bottom: 20px;">
                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn-login">
                <span class="spinner" id="spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
                Login
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px; color: #999; font-size: 12px;">
            <p>For demo access, contact administrator</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const spinner = document.getElementById('spinner');

            if (!username || !password) {
                alert('Please fill in all fields');
                return;
            }

            spinner.classList.add('show');

            fetch('/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
            })
            .then(response => response.json())
            .then(data => {
                spinner.classList.remove('show');

                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'Login failed');
                }
            })
            .catch(error => {
                spinner.classList.remove('show');
                console.error('Error:', error);
                alert('An error occurred during login');
            });
        });
    </script>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('/service-worker.js');
    });
}
</script>
<button id="installBtn" style="display:none;">
Install App
</button>

<script>
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    document.getElementById('installBtn').style.display = 'block';
});

document.getElementById('installBtn').addEventListener('click', async () => {
    deferredPrompt.prompt();
});
</script>

</body>
</html>
