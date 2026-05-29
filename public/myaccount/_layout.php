<?php
require_once dirname(dirname(__DIR__)) . "/src/Core/bootstrap.php";
require_once dirname(__DIR__) . "/config/urls.php";

$session = \App\Core\Session::getInstance();
$username = $session->getUsername() ?? 'Guest';
$userEmail = $_SESSION['user_email'] ?? 'test@example.com'; 
$currentPage = basename($_SERVER['SCRIPT_NAME'], '.php');

// Now include header (which outputs HTML)
require_once dirname(__DIR__) . "/components/header.php";
?>

<div class="min-h-screen bg-[#fafafa]">
    <!-- Breadcrumb -->
    <div class="px-8 md:px-16 pt-12 pb-6">
        <nav class="flex items-center gap-4 text-sm uppercase font-black tracking-[0.3em] text-gray-400">
            <span class="text-black italic">Your Account</span>
        </nav>
    </div>

    <!-- Main Grid Layout -->
    <div class="px-8 md:px-16 pb-32">
        <div class="max-w-[1440px] mx-auto lg:grid lg:grid-cols-[300px_1fr] gap-20">
            
            <!-- Sidebar Navigation -->
            <aside class="space-y-12 mb-16 lg:mb-0">
                <!-- User Profile Summary -->
                <div class="p-10 bg-white border border-gray-100 flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-black text-white text-xl font-black flex items-center justify-center rounded-full mb-6">
                        <?= strtoupper(substr($username, 0, 1)) ?>
                    </div>
                    <span class="text-[9px] uppercase font-black tracking-[0.4em] text-gold mb-1 block italic">Client Profile</span>
                    <h3 class="text-lg font-black uppercase tracking-widest truncate w-full mb-1"><?= htmlspecialchars($username) ?></h3>
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold truncate w-full"><?= htmlspecialchars($userEmail) ?></p>
                </div>

                <!-- Navigation Links -->
                <nav class="space-y-2">
                    <a href="<?= BASE_URL ?>myaccount/" class="flex items-center justify-between px-8 h-16 transition-all duration-300 group <?= $currentPage === 'index' ? 'bg-black text-white' : 'bg-white border border-gray-100 text-gray-500 hover:border-gold hover:text-black' ?>">
                        <span class="text-[10px] uppercase font-extrabold tracking-[0.3em]">Dashboard</span>
                        <span class="text-[9px] font-black <?= $currentPage === 'index' ? 'text-gold' : 'text-gray-200 group-hover:text-gold' ?>">01</span>
                    </a>
                    <a href="<?= BASE_URL ?>myaccount/orders.php" class="flex items-center justify-between px-8 h-16 transition-all duration-300 group <?= $currentPage === 'orders' ? 'bg-black text-white' : 'bg-white border border-gray-100 text-gray-500 hover:border-gold hover:text-black' ?>">
                        <span class="text-[10px] uppercase font-extrabold tracking-[0.3em]">Orders</span>
                        <span class="text-[9px] font-black <?= $currentPage === 'orders' ? 'text-gold' : 'text-gray-200 group-hover:text-gold' ?>">02</span>
                    </a>
                    <a href="<?= BASE_URL ?>myaccount/wishlist.php" class="flex items-center justify-between px-8 h-16 transition-all duration-300 group <?= $currentPage === 'wishlist' ? 'bg-black text-white' : 'bg-white border border-gray-100 text-gray-500 hover:border-gold hover:text-black' ?>">
                        <span class="text-[10px] uppercase font-extrabold tracking-[0.3em]">Wishlist</span>
                        <span class="text-[9px] font-black <?= $currentPage === 'wishlist' ? 'text-gold' : 'text-gray-200 group-hover:text-gold' ?>">03</span>
                    </a>
                    <a href="<?= BASE_URL ?>myaccount/addresses.php" class="flex items-center justify-between px-8 h-16 transition-all duration-300 group <?= $currentPage === 'addresses' ? 'bg-black text-white' : 'bg-white border border-gray-100 text-gray-500 hover:border-gold hover:text-black' ?>">
                        <span class="text-[10px] uppercase font-extrabold tracking-[0.3em] text-left">Saved Addresses</span>
                        <span class="text-[9px] font-black <?= $currentPage === 'addresses' ? 'text-gold' : 'text-gray-200 group-hover:text-gold' ?>">04</span>
                    </a>
                </nav>

                <!-- Footer Actions -->
                <div class="pt-12 border-t border-gray-200">
                    <a href="<?= BASE_URL ?>myaccount/logout.php" class="flex items-center gap-4 text-[9px] uppercase font-black tracking-[0.3em] text-gray-400 hover:text-red-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Sign Out
                    </a>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="space-y-12">
