<?php
require_once __DIR__ . '/../../src/Core/bootstrap.php';

// Redirect if already logged in as admin
if ($session->isLoggedIn() && $session->isAdmin()) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workstation Authorization | Royal Beverages</title>
    <link rel="stylesheet" href="assets/css/dashboard-tailwind.css">
    <style>
        body {
            background: #0a0a0c;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-family: 'Inter', system-ui, sans-serif;
            color: white;
        }

        .login-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(rgba(10, 10, 12, 0.85), rgba(10, 10, 12, 0.95)), 
                        url('assets/img/auth-bg.png');
            background-size: cover;
            background-position: center;
            filter: grayscale(20%);
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .glint {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        }

        .auth-header h1 {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #fff 0%, #a5a5a5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .auth-header p {
            color: #888;
            font-size: 13px;
            margin-bottom: 32px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #666;
            margin-bottom: 8px;
        }

        .auth-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 12px 16px;
            color: white;
            font-size: 14px;
            transition: all 0.2s ease;
            outline: none;
        }

        .auth-input:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.05);
        }

        .btn-auth {
            width: 100%;
            background: white;
            color: black;
            font-weight: 700;
            padding: 14px;
            border-radius: 12px;
            margin-top: 12px;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-auth:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px -5px rgba(255, 255, 255, 0.2);
        }

        .btn-auth:active {
            transform: translateY(0);
        }

        .btn-auth:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #ef4444;
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            display: none;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-4px); }
            75% { transform: translateX(4px); }
        }

        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(0,0,0,0.1);
            border-top-color: black;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-bg"></div>

    <div class="auth-card">
        <div class="glint"></div>
        
        <div class="auth-header">
            <h1>Royal Beverages</h1>
            <p>Vault Access Protocol & Admin Workstation</p>
        </div>

        <div id="error-banner" class="error-message"></div>

        <form id="login-form">
            <div class="form-group">
                <label class="form-label">Identifier (Email)</label>
                <input type="email" id="email" class="auth-input" placeholder="admin@royal-liquor.com" required autocomplete="email">
            </div>

            <div class="form-group">
                <label class="form-label">Authorization Key (Password)</label>
                <input type="password" id="password" class="auth-input" placeholder="••••••••" required autocomplete="current-password">
            </div>

            <button type="submit" id="submit-btn" class="btn-auth">
                <span>Unlock Workstation</span>
                <div class="spinner" id="spinner"></div>
            </button>
        </form>

        <div class="mt-8 text-center">
            <p class="text-[10px] text-[#444] uppercase tracking-widest font-bold">Secured by Royal Beverages Core v3.0</p>
        </div>
    </div>

    <script>
        const form = document.getElementById('login-form');
        const submitBtn = document.getElementById('submit-btn');
        const spinner = document.getElementById('spinner');
        const errorBanner = document.getElementById('error-banner');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            // Reset UI
            errorBanner.style.display = 'none';
            submitBtn.disabled = true;
            spinner.style.display = 'block';

            try {
                // API_BASE_URL = e.g. "http://localhost/api/v1/" — already contains the full base
                const apiUrl = '<?= rtrim(API_BASE_URL, "/") ?>/users/login';
                console.log('Attempting authentication against:', apiUrl);

                const response = await fetch(apiUrl, {
                    method: 'POST',
                    credentials: 'include', // Forces session cookie persistence
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                if (!response.ok) {
                    console.error('Fetch Response NOT OK:', response.status, response.statusText);
                    throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();
                console.log('Auth API Result:', result);

                if (!result.success) {
                    throw new Error(result.message || 'Authorization failed');
                }

                // Check if admin
                if (!result.data.is_admin) {
                     throw new Error('Access Revoked: Administrative privileges required.');
                }

                // Success! Redirect directly to the dashboard
                console.log('Login successful. Redirecting to index.php...');
                window.location.replace('index.php');

            } catch (err) {
                console.error('Login Exception:', err);
                errorBanner.textContent = err.message;
                errorBanner.style.display = 'block';
                submitBtn.disabled = false;
                spinner.style.display = 'none';
            }
        });
    </script>
</body>
</html>
