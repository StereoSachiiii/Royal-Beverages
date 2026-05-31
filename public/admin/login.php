<?php
require_once __DIR__ . '/../../src/Core/bootstrap.php';

// Redirect if already logged in as admin
if ($session->isLoggedIn() && $session->isAdmin()) {
    header("Location: index.php");
    exit;
}

$csrfToken = $session->getCsrfInstance()->getToken();
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sign In | Royal Beverages</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Inter:ital,opsz,wght@0,14..32,100..1000;1,14..32,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
    <script>
        const originalWarn = console.warn;
        console.warn = function(...args) {
            if (args[0] && typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com should not be used in production')) return;
            originalWarn.apply(console, args);
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --gold: #D4AF37;
            --gold-hover: #C49B28;
        }
        .text-gold { color: var(--gold); }
        .bg-gold { background-color: var(--gold); }
        .hover\:text-gold:hover { color: var(--gold); }
        .hover\:bg-gold:hover { background-color: var(--gold); }
        .border-gold { border-color: var(--gold); }
        .focus\:border-gold:focus { border-color: var(--gold); }
        .ring-gold\/20 { --tw-ring-color: rgba(212, 175, 55, 0.2); }
        .bg-gold\/5 { background-color: rgba(212, 175, 55, 0.05); }
        .border-gold\/20 { border-color: rgba(212, 175, 55, 0.2); }
        .selection\:bg-gold *::selection { background-color: var(--gold); }
        .selection\:bg-gold ::selection { background-color: var(--gold); }
        .auth-transition {
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .bg-noise {
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
            opacity: 0.05;
        }
    </style>
</head>
<body class="h-full font-sans antialiased text-slate-900 bg-white selection:bg-gold selection:text-white">

    <div class="grid grid-cols-1 lg:grid-cols-2 min-h-screen">
        
        <!-- Branding Panel (Left) - Hidden on mobile -->
        <section class="hidden lg:flex flex-col justify-between p-16 bg-black text-white relative overflow-hidden">
            <!-- Background Decorations -->
            <div class="absolute inset-0 bg-noise pointer-events-none"></div>
            <div class="absolute top-[-20%] right-[-10%] w-[80%] h-[80%] bg-gold/10 blur-[120px] rounded-full pointer-events-none"></div>
            
            <div class="relative z-10">
                <a href="../index.php" class="inline-block group">
                    <span class="text-xs font-black uppercase tracking-[0.4em] text-gold group-hover:text-white transition-colors">Royal Beverages</span>
                    <div class="h-px w-0 group-hover:w-full bg-white transition-all duration-500"></div>
                </a>
            </div>

            <div class="relative z-10 max-w-lg">
                <label class="text-[10px] uppercase font-black tracking-[0.4em] text-gold mb-4 block italic auth-transition">Admin Access</label>
                <h1 class="text-6xl font-black uppercase tracking-tighter leading-[0.9] mb-8 auth-transition">Manage the <br>Platform.</h1>
                <p class="text-gray-400 text-sm leading-relaxed max-w-sm font-medium auth-transition">
                    Access the admin dashboard to manage inventory, orders, users, and platform operations.
                </p>
                <div class="mt-12 h-[2px] w-24 bg-gold"></div>
            </div>

            <div class="relative z-10 flex justify-between items-center border-t border-white/10 pt-8">
                <span class="text-[9px] uppercase font-bold tracking-widest text-gray-500 italic">Royal Core v3.0</span>
                <div class="flex gap-4">
                    <div class="w-2 h-2 rounded-full bg-gold"></div>
                    <div class="w-2 h-2 rounded-full bg-white/10"></div>
                </div>
            </div>
        </section>

        <!-- Form Panel (Right) -->
        <main class="flex flex-col items-center justify-center p-8 md:p-16 lg:p-24 bg-white relative">
            <!-- Minimal Nav for Mobile -->
            <div class="lg:hidden absolute top-8 left-8">
                <a href="../index.php" class="text-[10px] font-black uppercase tracking-widest text-black">Royal Beverages</a>
            </div>

            <div class="w-full max-w-md">
                
                <!-- Login Module -->
                <div id="loginContainer">
                    <header class="mb-10">
                        <h2 class="text-4xl font-black uppercase tracking-tight text-black leading-none mb-4">Admin Sign In</h2>
                        <p class="text-sm text-gray-500 font-medium">Access the administrative dashboard.</p>
                    </header>
                    
                    <div id="loginMessage" class="mb-8 p-4 text-[10px] uppercase font-black tracking-widest hidden border border-red-200 bg-red-50 text-red-600"></div>

                    <form id="loginForm" class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[10px] uppercase font-black tracking-widest text-gray-400">Email Address</label>
                            <input type="email" name="email" required autocomplete="email" 
                                class="w-full h-14 bg-gray-50 border border-gray-300 px-6 text-sm font-bold focus:bg-white focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all rounded-none placeholder:text-gray-300"
                                placeholder="your@email.com">
                            <span class="error text-[9px] text-red-500 font-bold uppercase tracking-widest block mt-1" id="loginEmail-error"></span>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="text-[10px] uppercase font-black tracking-widest text-gray-400">Password</label>
                            <input type="password" name="password" required autocomplete="current-password" 
                                class="w-full h-14 bg-gray-50 border border-gray-300 px-6 text-sm font-bold focus:bg-white focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all rounded-none">
                            <span class="error text-[9px] text-red-500 font-bold uppercase tracking-widest block mt-1" id="loginPassword-error"></span>
                        </div>

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="login">
                        
                        <button type="submit" class="w-full h-16 bg-black text-white text-[10px] uppercase font-black tracking-[0.2em] hover:bg-gold transition-all duration-500 shadow-xl active:scale-[0.98] mt-4">
                            Sign In to Dashboard
                        </button>

                        <!-- OAuth Button -->
                        <button type="button" id="googleOAuthBtn" class="w-full h-16 bg-gray-100 border-2 border-gray-300 text-black text-[10px] uppercase font-black tracking-[0.2em] hover:bg-gray-200 hover:border-gray-400 transition-all duration-500 shadow-md active:scale-[0.98] flex items-center justify-center gap-3">
                            <svg class="w-5 h-5" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            Sign In with Google
                        </button>
                    </form>
                </div>

            </div>

            <!-- Footer Meta -->
            <div class="absolute bottom-8 text-center md:text-left text-gray-300 select-none">
                <p class="text-[8px] uppercase font-black tracking-[0.5em] leading-loose">
                    Admin Access Required <br class="md:hidden"> Secured & Monitored
                </p>
            </div>
        </main>
    </div>

    <script type="module">
        const loginForm = document.getElementById('loginForm');
        const googleOAuthBtn = document.getElementById('googleOAuthBtn');
        const loginMessage = document.getElementById('loginMessage');

        // Regular login
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(loginForm);
            const email = formData.get('email');
            const password = formData.get('password');

            loginMessage.classList.add('hidden');
            loginMessage.style.display = '';

            try {
                const apiUrl = '<?= rtrim(API_BASE_URL, "/") ?>/users/login';
                
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Login failed');
                }

                // Check if admin
                if (!result.data?.is_admin) {
                    throw new Error('Admin access required.');
                }

                // Success - redirect
                window.location.replace('index.php');

            } catch (err) {
                loginMessage.textContent = err.message;
                loginMessage.classList.remove('hidden');
            }
        });

        // Google OAuth
        googleOAuthBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            
            try {
                const response = await fetch('<?= rtrim(API_BASE_URL, "/") ?>/auth/google/redirect', {
                    method: 'GET',
                    credentials: 'include'
                });

                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    const result = await response.json();
                    if (result.data?.redirect_url) {
                        window.location.href = result.data.redirect_url;
                    }
                }
            } catch (err) {
                loginMessage.textContent = 'OAuth setup error: ' + err.message;
                loginMessage.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>
