<?php
$pageName = 'legal';
$pageTitle = 'Terms of Service - Royal Beverages';
require_once __DIR__ . '/components/header.php';
?>

<div class="max-w-[800px] mx-auto px-8 py-32 min-h-screen">
    <header class="mb-16">
        <span class="text-[10px] uppercase font-black tracking-[0.4em] text-gray-400 mb-4 block">Legal Documentation</span>
        <h1 class="text-4xl md:text-5xl font-heading tracking-widest uppercase mb-8">Terms of Service</h1>
        <div class="h-px bg-black w-32"></div>
    </header>

    <div class="prose prose-sm prose-black max-w-none space-y-12 text-sm leading-relaxed tracking-wide">
        <section>
            <h2 class="text-xs uppercase font-black tracking-widest mb-4">1. Acceptance of Terms</h2>
            <p class="text-gray-600">By accessing the Royal Beverages online storefront, you confirm you are of legal drinking age in your jurisdiction and agree to be bound by these terms. Continued usage indicates absolute consent.</p>
        </section>

        <section>
            <h2 class="text-xs uppercase font-black tracking-widest mb-4">2. Prohibition & Age Verification</h2>
            <p class="text-gray-600">Alcoholic beverages may only be ordered by individuals over the age of 21 (or applicable regional law). Consignees must provide valid photo ID upon physical delivery matching the billing footprint.</p>
        </section>

        <section>
            <h2 class="text-xs uppercase font-black tracking-widest mb-4">3. Product Accuracy & Liability</h2>
            <p class="text-gray-600">We strive for exact precision regarding stock dimensions and vintage labeling. However, we are not liable for slight vintage substitutions in the event of immediate depletion, provided the value remains equal or greater.</p>
        </section>

        <section>
            <h2 class="text-xs uppercase font-black tracking-widest mb-4">4. Intellectual Property</h2>
            <p class="text-gray-600">The "Royal Beverages" trademark, monochrome digital layouts, aesthetic implementations, and backend AI architecture are proprietary.</p>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
