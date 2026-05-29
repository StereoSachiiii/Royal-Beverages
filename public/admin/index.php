<?php
// 1. Initialize System via Bootstrapper
require_once __DIR__ . '/../../src/Core/bootstrap.php';
$session = \App\Core\Session::getInstance();

// [GUARD] Ensure user is authenticated and is an administrator
// $session was initialized in bootstrap.php
if (!$session->isLoggedIn() || !$session->isAdmin()) {
    header("Location: login.php");
    exit;
}

$currentUser = [
    "user_id" => $session->get("user_id"),
    "username" => $session->get("username"),
    "email" => $session->get("email"),
    "is_admin" => $session->get("is_admin"),
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Royal Beverages Premium</title>

    <!-- Tailwind Play CDN for dynamic utility class support -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        black: '#111111',
                        gold: '#D4AF37',
                        slate: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['DM Sans', 'sans-serif'],
                    },
                    letterSpacing: {
                        premium: '.3em',
                    }
                }
            }
        }
    </script>

    <!-- Chart.js for interactive visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Inter:ital,opsz,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/dashboard-tailwind.css">
    
    <script>
        // Environment bridge from PHP to JavaScript
        window.ADMIN_CONFIG = {
            BASE_URL: '<?= BASE_URL ?>',
            API_BASE_URL: '<?= API_BASE_URL ?>'
        };
    </script>
</head>
<<body>
    <?php include "includes/templates.php"; ?>
    
    <!-- Mobile Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black/50 z-40 hidden transition-opacity opacity-0 md:hidden pointer-events-none"></div>

    <!-- ================= Sidebar ================= -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-black transform -translate-x-full md:translate-x-0 transition-transform duration-500 overflow-y-auto shadow-2xl md:shadow-none">
        <div class="h-20 flex items-center px-8 border-b border-white/10">
            <h2 class="text-xl font-heading font-black tracking-widest m-0 text-white uppercase italic">Royal <span class="text-gold">Admin</span></h2>
        </div>
        
        <ul class="sidebar-menu p-6 flex flex-col gap-2 m-0" style="list-style: none;">
            <!-- Renders via JS template 'tpl-admin-sidebar-nav' -->
        </ul>
    </aside>
 
    <!-- ================= Main Wrapper ================= -->
    <div class="flex-1 md:pl-64 flex flex-col min-h-screen transition-all transform w-full bg-slate-50">
        
        <!-- ================= Header ================= -->
        <header id="admin-header" class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-6 md:px-10 sticky top-0 z-30 shadow-sm">
            <div class="flex items-center gap-6">
                <button id="mobile-menu-toggle" class="md:hidden p-2 text-gray-500 hover:text-black hover:bg-gray-100 rounded-lg transition-all cursor-pointer" aria-label="Toggle menu" style="margin-left: -0.5rem;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="flex items-center gap-4">
                    <h1 class="text-lg font-heading font-extrabold tracking-tight text-black m-0 hidden lg:block border-r border-gray-200 pr-6">Management</h1>
                    <div id="breadcrumb" class="text-xs uppercase tracking-widest text-gray-400 font-bold"> <!-- Breadcrumb renders here --> </div>
                </div>
            </div>
            
            <div class="flex items-center gap-6">
                <!-- User Profile -->
                <div class="flex items-center gap-3 pr-6 border-r border-gray-100 hidden sm:flex">
                    <div class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center font-bold text-xs"><?= strtoupper(substr($currentUser['username'] ?? 'A', 0, 1)) ?></div>
                    <span class="font-bold text-xs tracking-wide text-black uppercase"><?= htmlspecialchars($currentUser['username'] ?? 'Admin') ?></span>
                </div>
                <a href="<?= BASE_URL ?>admin/logout.php" class="text-[10px] font-black tracking-premium uppercase text-gray-500 hover:text-black transition-colors">Sign Out</a>
            </div>
        </header>

        <div id="modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden relative">
                <div id="modal-body" class="p-6"></div>
                <span id="modal-close" class="absolute top-4 right-4 cursor-pointer text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</span>
            </div>
        </div>

        <!-- ================= Main Content ================= -->
        <main id="content" class="flex-1 overflow-x-hidden overflow-y-auto">
            <!-- Dynamic admin content loaded here -->
        </main>
    </div>

<script type="module" src="js/router.js"></script>
<script>
// Vanilla JS layout toggle utilizing Tailwind utility classes exclusively
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-overlay');
    
    function toggleSidebar(forceClose = false) {
        if (forceClose) {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            overlay.classList.add('hidden', 'opacity-0', 'pointer-events-none');
        } else {
            const isOpen = sidebar.classList.contains('translate-x-0');
            if (isOpen) {
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
                overlay.classList.add('opacity-0', 'pointer-events-none');
                setTimeout(() => overlay.classList.add('hidden'), 500);
            } else {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.remove('opacity-0', 'pointer-events-none'), 10);
            }
        }
    }

    if (mobileMenuToggle && sidebar) {
        mobileMenuToggle.addEventListener('click', () => toggleSidebar());
        if (overlay) overlay.addEventListener('click', () => toggleSidebar(true));
    }
    
    // Close sidebar on link click (mobile)
    document.addEventListener('click', (e) => {
        if (e.target.closest('.sidebar-link') && window.innerWidth < 768) {
            toggleSidebar(true);
        }
    });
    
    // Modal close handlers
    const modalClose = document.getElementById('modal-close');
    const modal = document.getElementById('modal');
    if (modalClose && modal) {
        modalClose.addEventListener('click', () => modal.classList.add('hidden'));
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.add('hidden');
        });
    }
});
</script>
</body>
</html>
