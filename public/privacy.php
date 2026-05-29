<?php
$pageName = 'legal';
$pageTitle = 'Privacy Policy - Royal Beverages';
require_once __DIR__ . '/components/header.php';
?>

<div class="max-w-[800px] mx-auto px-8 py-32 min-h-screen">
    <header class="mb-16">
        <span class="text-[10px] uppercase font-black tracking-[0.4em] text-gray-400 mb-4 block">Legal Documentation</span>
        <h1 class="text-4xl md:text-5xl font-heading tracking-widest uppercase mb-8">Privacy Policy</h1>
        <div class="h-px bg-black w-32"></div>
    </header>

    <div class="prose prose-sm prose-black max-w-none space-y-12 text-sm leading-relaxed tracking-wide">
        <section>
            <h2 class="text-xs uppercase font-black tracking-widest mb-4">1. Collection of Data</h2>
            <p class="text-gray-600">Royal Beverages ("we", "us", "our") respects your privacy. We collect personal data when you interact with our Services, place an order, or subscribe to our newsletter. This data includes names, emails, shipping addresses, and purchase histories.</p>
        </section>

        <section>
            <h2 class="text-xs uppercase font-black tracking-widest mb-4">2. Usage of Data</h2>
            <p class="text-gray-600">Your data is strictly utilized to process transactions, fulfill deliveries, and power your exclusive "For You" artificial intelligence recommendations engine safely. We will never sell your personal footprint to third parties.</p>
        </section>

        <section>
            <h2 class="text-xs uppercase font-black tracking-widest mb-4">3. Cookie Policy</h2>
            <p class="text-gray-600">We utilize functional and securely encrypted session cookies to manage authentication states and local `localStorage` algorithms to construct responsive wishlists. Consent is logged upon acceptance of our gateway overlay.</p>
        </section>

        <section>
            <h2 class="text-xs uppercase font-black tracking-widest mb-4">4. Compliance & Data Rights</h2>
            <p class="text-gray-600">You may request the deletion, exportation, or anonymization of your data at any time via your account portal. Operations adhere to standard eCommerce transaction encryption schemas over HTTPS.</p>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
