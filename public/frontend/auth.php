<?php
require_once __DIR__ . '/../../src/Core/bootstrap.php';

use App\Core\Session;
use App\Core\CSRF;

$session = \App\Core\Session::getInstance();

// If already logged in, redirect to home or intended page
if ($session->isLoggedIn()) {
    $redirect = $_GET['redirect'] ?? 'index.php';
    header("Location: $redirect");
    exit;
}

$csrfToken = $session->getCsrfInstance()->getToken();
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Identity | Royal Beverages</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Inter:ital,opsz,wght@0,14..32,100..1000;1,14..32,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
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
                <a href="index.php" class="inline-block group">
                    <span class="text-xs font-black uppercase tracking-[0.4em] text-gold group-hover:text-white transition-colors">Royal Beverages</span>
                    <div class="h-px w-0 group-hover:w-full bg-white transition-all duration-500"></div>
                </a>
            </div>

            <div class="relative z-10 max-w-lg">
                <label id="panelSubtext" class="text-[10px] uppercase font-black tracking-[0.4em] text-gold mb-4 block italic auth-transition">Identity Verification</label>
                <h1 id="panelTitle" class="text-6xl font-black uppercase tracking-tighter leading-[0.9] mb-8 auth-transition">Access the <br>Collection.</h1>
                <p id="panelDescription" class="text-gray-400 text-sm leading-relaxed max-w-sm font-medium auth-transition">
                    Sign in to manage your private cellar, access curated selections, and track your active acquisitions.
                </p>
                <div class="mt-12 h-[2px] w-24 bg-gold"></div>
            </div>

            <div class="relative z-10 flex justify-between items-center border-t border-white/10 pt-8">
                <span class="text-[9px] uppercase font-bold tracking-widest text-gray-500 italic">Est. MCMLXXXI</span>
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
                <a href="index.php" class="text-[10px] font-black uppercase tracking-widest text-black">Royal Beverages</a>
            </div>

            <div class="w-full max-w-md">
                
                <!-- Login Module -->
                <div id="loginContainer" class="auth-module auth-transition opacity-100 block">
                    <header class="mb-10">
                        <h2 class="text-4xl font-black uppercase tracking-tight text-black leading-none mb-4">Sign In</h2>
                        <p class="text-sm text-gray-500 font-medium">Access your personal vault to manage acquisitions.</p>
                    </header>
                    
                    <div id="loginMessage" class="mb-8 p-4 text-[10px] uppercase font-black tracking-widest hidden border border-gold/20 bg-gold/5 text-gold-hover"></div>

                    <form id="loginForm" class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[10px] uppercase font-black tracking-widest text-gray-400">Email Address</label>
                            <input type="email" name="email" required autocomplete="email" 
                                class="w-full h-14 bg-gray-50 border border-gray-300 px-6 text-sm font-bold focus:bg-white focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all rounded-none placeholder:text-gray-300"
                                placeholder="name@example.com">
                            <span class="error text-[9px] text-red-500 font-bold uppercase tracking-widest block mt-1" id="loginEmail-error"></span>
                        </div>
                        
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <label class="text-[10px] uppercase font-black tracking-widest text-gray-400">Password</label>
                                <a href="#" class="text-[9px] uppercase font-black tracking-widest text-gray-300 hover:text-gold transition-colors">Forgotten?</a>
                            </div>
                            <input type="password" name="password" required autocomplete="current-password" 
                                class="w-full h-14 bg-gray-50 border border-gray-300 px-6 text-sm font-bold focus:bg-white focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all rounded-none">
                            <span class="error text-[9px] text-red-500 font-bold uppercase tracking-widest block mt-1" id="loginPassword-error"></span>
                        </div>

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="login">
                        
                        <button type="submit" class="w-full h-16 bg-black text-white text-[10px] uppercase font-black tracking-[0.2em] hover:bg-gold transition-all duration-500 shadow-xl active:scale-[0.98] mt-4">
                            Log In to Your Cellar
                        </button>
                    </form>

                    <footer class="mt-12 pt-8 border-t border-gray-100 text-center">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mr-2">New here?</span>
                        <button onclick="toggleAuth('signup')" class="text-[10px] font-black uppercase tracking-[0.2em] text-gold hover:text-black transition-colors underline underline-offset-4 decoration-2">Create Membership</button>
                    </footer>
                </div>

                <!-- Registration Module -->
                <div id="signupContainer" class="auth-module auth-transition opacity-0 hidden">
                    <header class="mb-10">
                        <h2 class="text-4xl font-black uppercase tracking-tight text-black leading-none mb-4">Register</h2>
                        <p class="text-sm text-gray-500 font-medium">Join our private network of fine spirits collectors.</p>
                    </header>

                    <div id="signupMessage" class="mb-8 p-4 text-[10px] uppercase font-black tracking-widest hidden border border-gold/20 bg-gold/5 text-gold-hover"></div>

                    <form id="signupForm" class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[10px] uppercase font-black tracking-widest text-gray-400">Full Name</label>
                            <input type="text" name="name" required minlength="2" maxlength="100" 
                                class="w-full h-14 bg-gray-50 border border-gray-300 px-6 text-sm font-bold focus:bg-white focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all rounded-none">
                            <span class="error text-[9px] text-red-500 font-bold uppercase tracking-widest block mt-1" id="name-error"></span>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] uppercase font-black tracking-widest text-gray-400">Email Address</label>
                            <input type="email" name="email" required 
                                class="w-full h-14 bg-gray-50 border border-gray-300 px-6 text-sm font-bold focus:bg-white focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all rounded-none">
                            <span class="error text-[9px] text-red-500 font-bold uppercase tracking-widest block mt-1" id="email-error"></span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase font-black tracking-widest text-gray-400">Password</label>
                                <input type="password" name="password" required minlength="8" 
                                    class="w-full h-14 bg-gray-50 border border-gray-300 px-6 text-sm font-bold focus:bg-white focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all rounded-none">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase font-black tracking-widest text-gray-400">Confirm</label>
                                <input type="password" name="confirm_password" required 
                                    class="w-full h-14 bg-gray-50 border border-gray-300 px-6 text-sm font-bold focus:bg-white focus:border-gold focus:ring-2 focus:ring-gold/20 outline-none transition-all rounded-none">
                            </div>
                            <div class="col-span-full">
                                <span class="error text-[9px] text-red-500 font-bold uppercase tracking-widest block" id="password-error"></span>
                                <span class="error text-[9px] text-red-500 font-bold uppercase tracking-widest block" id="confirm_password-error"></span>
                            </div>
                        </div>

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="register">
                        
                        <button type="submit" class="w-full h-16 bg-black text-white text-[10px] uppercase font-black tracking-[0.2em] hover:bg-gold transition-all duration-500 shadow-xl active:scale-[0.98] mt-4">
                            Join the Private Collection
                        </button>
                    </form>

                    <footer class="mt-12 pt-8 border-t border-gray-100 text-center">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mr-2">Registered?</span>
                        <button onclick="toggleAuth('login')" class="text-[10px] font-black uppercase tracking-[0.2em] text-gold hover:text-black transition-colors underline underline-offset-4 decoration-2">Access Account</button>
                    </footer>
                </div>

            </div>

            <!-- Footer Meta -->
            <div class="absolute bottom-8 text-center md:text-left text-gray-300 select-none">
                <p class="text-[8px] uppercase font-black tracking-[0.5em] leading-loose">
                    Automated Verification System <br class="md:hidden"> Powered by Royal Core Security
                </p>
            </div>
        </main>
    </div>

    <!-- Core Logic -->
    <script type="module">
        import { apiRequest } from './assets/js/api-helper.js';

        const loginContainer = document.getElementById('loginContainer');
        const signupContainer = document.getElementById('signupContainer');
        const panelTitle = document.getElementById('panelTitle');
        const panelSubtext = document.getElementById('panelSubtext');
        const panelDescription = document.getElementById('panelDescription');

        function toggleAuth(mode) {
            if (mode === 'signup') {
                // UI Toggle
                loginContainer.classList.add('opacity-0', 'pointer-events-none');
                setTimeout(() => {
                    loginContainer.classList.add('hidden');
                    signupContainer.classList.remove('hidden');
                    setTimeout(() => {
                        signupContainer.classList.remove('opacity-0', 'pointer-events-none');
                    }, 50);
                }, 500);

                // Side Panel Update
                panelSubtext.innerText = 'New Acquisition';
                panelTitle.innerHTML = 'Join the <br>Network.';
                panelDescription.innerText = 'Become a private member to unlock global shipping, exclusive vintage releases, and bespoke concierge services.';
            } else {
                // UI Toggle
                signupContainer.classList.add('opacity-0', 'pointer-events-none');
                setTimeout(() => {
                    signupContainer.classList.add('hidden');
                    loginContainer.classList.remove('hidden');
                    setTimeout(() => {
                        loginContainer.classList.remove('opacity-0', 'pointer-events-none');
                    }, 50);
                }, 500);

                // Side Panel Update
                panelSubtext.innerText = 'Identity Verification';
                panelTitle.innerHTML = 'Access the <br>Collection.';
                panelDescription.innerText = 'Sign in to manage your private cellar, access curated selections, and track your active acquisitions.';
            }
        }
        window.toggleAuth = toggleAuth;

        async function handleAuth(formId, endpoint) {
            const form = document.getElementById(formId);
            const messageDiv = document.getElementById(formId.replace('Form', 'Message'));
            const submitBtn = form.querySelector('button[type="submit"]');
            
            // Clear errors
            form.querySelectorAll('.error').forEach(e => e.innerText = '');
            messageDiv.classList.add('hidden');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                submitBtn.disabled = true;
                submitBtn.innerText = 'Processing...';

                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());

                try {
                    const response = await apiRequest(endpoint, { 
                        method: 'POST', 
                        body: data 
                    });
                    
                    if (response.success) {
                        messageDiv.className = 'mb-8 p-4 text-[10px] uppercase font-black tracking-widest border border-green-500 bg-green-50 text-green-700';
                        messageDiv.innerText = response.message || 'Verification Successful. Redirecting...';
                        messageDiv.classList.remove('hidden');
                        
                        setTimeout(() => {
                            let redirect = new URLSearchParams(window.location.search).get('redirect') || 'index.php';
                            if (redirect && !redirect.endsWith('.php')) {
                                redirect += '.php';
                            }
                            window.location.href = redirect;
                        }, 1000);
                    } else {
                        throw response;
                    }
                } catch (error) {
                    submitBtn.disabled = false;
                    submitBtn.innerText = formId === 'loginForm' ? 'Establish Connection' : 'Apply for Membership';
                    
                    if (error.errors) {
                        messageDiv.className = 'mb-8 p-4 text-[10px] uppercase font-black tracking-widest border border-red-500 bg-red-50 text-red-700';
                        messageDiv.innerText = 'Please correct the errors below.';
                        messageDiv.classList.remove('hidden');

                        Object.entries(error.errors).forEach(([key, msg]) => {
                            const errorEl = document.getElementById(`${(formId.startsWith('login') ? 'login' : '') + key}-error`);
                            if (errorEl) {
                                errorEl.innerText = Array.isArray(msg) ? msg[0] : msg;
                            }
                        });
                    } else {
                        messageDiv.className = 'mb-8 p-4 text-[10px] uppercase font-black tracking-widest border border-red-500 bg-red-50 text-red-700';
                        messageDiv.innerText = error.message || 'System error during verification.';
                        messageDiv.classList.remove('hidden');
                    }
                }
            });
        }

        handleAuth('loginForm', '/users/login');
        handleAuth('signupForm', '/users/register');
    </script>
</body>
</html>
