<?php
require_once __DIR__ . "/../../../src/Core/bootstrap.php";
require_once __DIR__ . "/../../config/urls.php";
require_once __DIR__ . "/../../config/page-assets.php";

$session = \App\Core\Session::getInstance();
$username = $session->getUsername();
$pageName = $pageName ?? 'home'; 
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Royal Beverages - Premium spirits and fine wines">
    <title><?= $pageTitle ?? 'Royal Beverages - Premium Spirits' ?></title>
    <link rel="icon" type="image/png" href="<?= ASSET_URL ?>images/favicon.webp">
    
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <!-- Tailwind Entry -->
    <?php loadPageCSS($pageName); ?>

    <script>
        window.ROYAL_CONFIG = {
            VERSION: '2.0.0',
            BASE_URL: '<?= BASE_URL ?>',
            ASSET_URL: '<?= ASSET_URL ?>',
            API_BASE_URL: '<?= API_BASE_URL ?>',
            IS_DEBUG: <?= defined('DEBUG') ? (DEBUG ? 'true' : 'false') : 'true' ?>,
            IS_LOCAL: <?= (str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost') || ($_SERVER['HTTP_HOST'] ?? '') === '127.0.0.1') ? 'true' : 'false' ?>
        };
    </script>
</head>
<body class="bg-white text-black font-body antialiased transition-colors duration-300">

<!-- Global Overlay -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[900] opacity-0 invisible transition-all duration-500"></div>

<!-- Mobile Drawer (Left) -->
<aside id="mobileSidebar" class="fixed top-0 left-0 h-full w-[400px] max-w-full bg-white z-[1000] -translate-x-full transition-transform duration-500 shadow-2xl flex flex-col">
    <!-- Sidebar Header -->
    <div class="flex items-center justify-between p-10 border-b border-gray-50">
        <div class="flex flex-col">
            <h2 class="text-xl font-heading tracking-widest uppercase">The Menu</h2>
        </div>
        <button id="mobileClose" class="w-12 h-12 flex items-center justify-center border border-gray-100 hover:bg-black hover:text-white transition-all duration-300 group">
            <svg class="w-5 h-5 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    
    <!-- Main Navigation -->
    <nav class="flex-grow p-12 overflow-y-auto">
        <ul class="space-y-10 list-none p-0 m-0">
            <li class="group">
                <a href="<?= BASE_URL ?>" class="flex items-end justify-between hover:text-gray-500 transition-all duration-300">
                    <span class="text-3xl font-heading font-normal uppercase tracking-widest leading-none">Home</span>
                    <span class="text-[10px] font-heading font-normal text-gray-200 group-hover:text-gray-400 transition-colors">01</span>
                </a>
            </li>
            <li class="group">
                <a href="<?= PAGE_URLS['shop'] ?>" class="flex items-end justify-between hover:text-gray-500 transition-all duration-300">
                    <span class="text-3xl font-heading font-normal uppercase tracking-widest leading-none">Shop</span>
                    <span class="text-[10px] font-heading font-normal text-gray-200 group-hover:text-gray-400 transition-colors">02</span>
                </a>
            </li>
            <li class="group">
                <a href="<?= PAGE_URLS['shop'] ?>#categoryFilters" class="flex items-end justify-between hover:text-gray-500 transition-all duration-300">
                    <span class="text-3xl font-heading font-normal uppercase tracking-widest leading-none">Categories</span>
                    <span class="text-[10px] font-heading font-normal text-gray-200 group-hover:text-gray-400 transition-colors">03</span>
                </a>
            </li>
            <li class="group">
                <a href="<?= BASE_URL ?>recipes.php" class="flex items-end justify-between hover:text-gray-500 transition-all duration-300">
                    <span class="text-3xl font-heading font-normal uppercase tracking-widest leading-none">Recipes</span>
                    <span class="text-[10px] font-heading font-normal text-gray-200 group-hover:text-gray-400 transition-colors">04</span>
                </a>
            </li>
            <li class="group">
                <a href="<?= BASE_URL ?>feedback.php" class="flex items-end justify-between hover:text-gray-500 transition-all duration-300">
                    <span class="text-3xl font-heading font-normal uppercase tracking-widest leading-none">Reviews</span>
                    <span class="text-[10px] font-heading font-normal text-gray-200 group-hover:text-gray-400 transition-colors">05</span>
                </a>
            </li>
            <li class="group">
                <a href="<?= BASE_URL ?>faq.php" class="flex items-end justify-between hover:text-gray-500 transition-all duration-300">
                    <span class="text-3xl font-heading font-normal uppercase tracking-widest leading-none">FAQ</span>
                    <span class="text-[10px] font-heading font-normal text-gray-200 group-hover:text-gray-400 transition-colors">06</span>
                </a>
            </li>
        </ul>

        <div class="mt-20 pt-12 border-t border-gray-50 space-y-4">
            <a href="<?= PAGE_URLS['account'] ?>" class="block text-sm font-medium text-black hover:text-gray-500 transition-colors">My Account</a>
            <a href="<?= PAGE_URLS['contact'] ?>" class="block text-sm font-medium text-black hover:text-gray-500 transition-colors">Contact Us</a>
        </div>
    </nav>

    <!-- Sidebar Footer -->
    <div class="p-10 bg-gray-50 flex items-center justify-between">
        <div class="flex gap-6">
            <a href="#" class="text-xs font-black uppercase tracking-widest hover:text-gray-400">IG</a>
            <a href="#" class="text-xs font-black uppercase tracking-widest hover:text-gray-400">FB</a>
            <a href="#" class="text-xs font-black uppercase tracking-widest hover:text-gray-400">TW</a>
        </div>
        <span class="text-[8px] font-black uppercase tracking-[0.4em] text-gray-300">© 2026 RK Reserve</span>
    </div>
</aside>

<!-- Profile Drawer (Right) -->
<aside id="profileSidebar" class="fixed top-0 right-0 h-full w-[350px] bg-white z-[1000] translate-x-full transition-transform duration-500 shadow-2xl overflow-y-auto">
    <div class="p-12 text-center border-b border-gray-100 bg-gray-50/50">
        <div class="w-20 h-20 bg-black text-white rounded-full flex items-center justify-center mx-auto mb-6 text-2xl font-bold">
            <?= strtoupper(substr($username ?? 'G', 0, 1)) ?>
        </div>
        <p class="text-xs uppercase tracking-[0.3em] text-muted mb-1">Welcome</p>
        <h3 class="text-xl font-heading tracking-widest uppercase truncate"><?= htmlspecialchars($username ?? 'Guest') ?></h3>
    </div>
    
    <div class="p-8 flex flex-col gap-3">
        <a href="<?= PAGE_URLS['account'] ?>" class="btn-premium w-full">Dashboard</a>
        <a href="<?= PAGE_URLS['orders'] ?>" class="btn-premium-outline w-full">Order History</a>
        
        <?php if($session->isLoggedIn()): ?>
            <a href="<?= PAGE_URLS['logout'] ?>" class="mt-8 text-center text-xs uppercase tracking-widest font-extrabold text-gray-400 hover:text-black transition-colors">Sign Out</a>
        <?php else: ?>
            <a href="<?= PAGE_URLS['login'] ?>" class="btn-premium w-full mt-4">Sign In</a>
        <?php endif; ?>
    </div>
</aside>

<!-- Desktop Header -->
<header class="sticky top-0 z-[100] bg-white h-[100px] transition-all duration-300 border-b border-gray-100 px-8">
    <div class="max-w-[1440px] mx-auto w-full h-full flex items-center">
        <!-- Left: Menu -->
        <div class="flex-1 flex items-center">
            <button id="menu" class="group flex items-center gap-3 py-2 px-1 text-black uppercase text-[10px] tracking-[0.3em] font-extrabold hover:text-gray-500 transition-all">
                <div class="flex flex-col gap-1.5 w-6">
                    <span class="h-[1.5px] w-full bg-current transition-all group-hover:w-1/2"></span>
                    <span class="h-[1.5px] w-full bg-current"></span>
                    <span class="h-[1.5px] w-3/4 bg-current transition-all group-hover:w-full"></span>
                </div>
                <span class="hidden lg:block">The Collection</span>
            </button>
        </div>

        <!-- Center: Brand -->
        <div class="flex-1 flex justify-center text-center">
            <a href="<?= BASE_URL ?>" class="block group">
                <span class="text-2xl font-heading tracking-[0.4em] uppercase font-extrabold group-hover:text-gray-600 transition-colors">Royal Beverages</span>
            </a>
        </div>

        <!-- Right: Actions -->
        <div class="flex-1 flex items-center justify-end gap-4 lg:gap-8">
            <!-- Search -->
            <div class="relative group hidden sm:block">
                <div class="search-wrapper flex items-center bg-gray-50 border border-gray-100 rounded-full px-3 h-10 transition-all duration-500 overflow-hidden w-12">
                    <button id="searchBtn" class="shrink-0 p-1 hover:text-gray-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    </button>
                    <input type="search" placeholder="Search..." id="searchInput" class="bg-transparent border-none outline-none text-[10px] px-2 w-0 opacity-0 transition-all duration-300 font-black uppercase tracking-widest placeholder:text-gray-300">
                    <button id="searchCloseBtn" class="hidden shrink-0 hover:text-red-500 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
            </div>

            <!-- Cart -->
            <a href="<?= PAGE_URLS['cart'] ?>" id="cart" class="group relative p-2 text-black hover:text-gray-500 transition-all duration-300">
                <div class="count-display absolute top-1 right-1 bg-black text-white text-[8px] font-black w-4 h-4 flex items-center justify-center rounded-full border border-white shadow-sm ring-2 ring-transparent group-hover:ring-black transition-all hidden">0</div>
                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </a>

            <!-- Profile -->
            <button id="profile" class="p-2 text-black hover:text-gray-500 transition-all duration-300 hover:scale-110">
                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </button>
        </div>
    </div>
</header>


<!-- Dynamic JS Loading -->
<script src="<?= ASSET_URL ?>js/header.js" type="module"></script>
<script src="<?= ASSET_URL ?>js/search.js" type="module"></script>
