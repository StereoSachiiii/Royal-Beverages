<?php
require_once __DIR__ . "/../config/urls.php";
?>

<footer class="bg-black text-white py-12 px-8 md:px-16 mt-20">
    <div class="max-w-[1440px] mx-auto grid grid-cols-1 md:grid-cols-4 gap-8 md:gap-12 text-left">
        <!-- Brand Section -->
        <div class="space-y-4">
            <h2 class="text-xl font-heading tracking-[.3em] uppercase font-extrabold italic">Royal Beverages</h2>
            <p class="text-white/70 text-xs leading-relaxed font-light tracking-wide italic max-w-xs">
                Purveyors of the world's finest spirits, rare vintages, and artisanal craft beverages since 1924.
            </p>
            <div class="flex gap-4">
                <a href="#" class="text-white/70 hover:text-white transition-colors text-xs">Twitter</a>
                <a href="#" class="text-white/70 hover:text-white transition-colors text-xs">Instagram</a>
                <a href="#" class="text-white/70 hover:text-white transition-colors text-xs">Facebook</a>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="space-y-4">
            <h3 class="text-xs uppercase tracking-[.2em] font-extrabold text-white">The Collection</h3>
            <ul class="space-y-2 list-none p-0">
                <li><a href="<?= PAGE_URLS['shop'] ?>" class="text-xs text-white/80 hover:text-white transition-colors tracking-wide">Shop All</a></li>
            </ul>
        </div>

        <!-- Experience -->
        <div class="space-y-4">
            <h3 class="text-xs uppercase tracking-[.2em] font-extrabold text-white">Experience</h3>
            <ul class="space-y-2 list-none p-0">
                <li><a href="<?= PAGE_URLS['contact'] ?>" class="text-xs text-white/80 hover:text-white transition-colors tracking-wide">Private Concierge</a></li>
                <li><a href="<?= PAGE_URLS['feedback'] ?>" class="text-xs text-white/80 hover:text-white transition-colors tracking-wide">Customer Feedback</a></li>
            </ul>
        </div>

        <!-- Newsletter -->
        <div class="space-y-4">
            <h3 class="text-xs uppercase tracking-[.2em] font-extrabold text-white">The Journal</h3>
            <p class="text-[11px] text-white/70 uppercase tracking-widest leading-loose">
                Subscribe for exclusive release updates.
            </p>
            <form class="flex gap-2">
                <input type="email" placeholder="Email Address" class="bg-gray-900 border border-gray-800 px-4 py-2 outline-none text-xs w-full uppercase tracking-widest placeholder:text-gray-700 text-white">
                <button type="submit" class="bg-white text-black px-4 text-xs uppercase font-bold hover:bg-gray-100 transition-colors">Join</button>
            </form>
        </div>
    </div>

    <!-- Copyright -->
    <div class="max-w-[1440px] mx-auto mt-12 pt-6 border-t border-gray-900 flex flex-col md:flex-row justify-between items-center gap-4 text-[9px] uppercase tracking-[0.2em] text-white/60 font-bold">
        <span class="italic text-white/60">&copy; <?= date('Y') ?> Royal Beverages. All rights reserved.</span>
        <div class="flex gap-6">
            <a href="<?= BASE_URL ?>privacy.php" class="text-white/80 hover:text-white">Privacy Policy</a>
            <a href="<?= BASE_URL ?>terms.php" class="text-white/80 hover:text-white">Terms of Release</a>
            <a href="<?= BASE_URL ?>returns.php" class="text-white/80 hover:text-white">Returns & Refunds</a>
        </div>
    </div>
</footer>

<script src="<?= ASSET_URL ?>js/toast.js" type="module"></script>
</body>
</html>
