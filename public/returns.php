<?php
$pageName = 'legal';
$pageTitle = 'Returns Policy - Royal Beverages';
require_once __DIR__ . '/components/header.php';
?>

<div class="max-w-[800px] mx-auto px-8 py-32 min-h-screen">
    <header class="mb-16">
        <span class="text-[10px] uppercase font-black tracking-[0.4em] text-gray-400 mb-4 block">Legal Documentation</span>
        <h1 class="text-4xl md:text-5xl font-heading tracking-widest uppercase mb-8">Returns & Refunds</h1>
        <div class="h-px bg-black w-32"></div>
    </header>

    <div class="prose prose-sm prose-black max-w-none space-y-12 text-sm leading-relaxed tracking-wide">
        <section>
            <h2 class="text-xs uppercase font-black tracking-widest mb-4">1. General Policy</h2>
            <p class="text-gray-600">Due to the perishable and heavily legislated nature of alcoholic goods, Royal Beverages operates an exceptionally strict returns architecture. All direct sales are final upon digital settlement.</p>
        </section>

        <section>
            <h2 class="text-xs uppercase font-black tracking-widest mb-4">2. Defective or Damaged Goods</h2>
            <p class="text-gray-600">If your package arrives physically compromised (shattered glass, heavily compromised corking, transit heat damage), you must notify the concierge within 48 hours of timestamped delivery. Provide photographic evidence through the contact portal. A direct replacement or full financial reverse-authorization will be initiated.</p>
        </section>

        <section>
            <h2 class="text-xs uppercase font-black tracking-widest mb-4">3. Corked Wine</h2>
            <p class="text-gray-600">We stand by the vintage integrity of our wines. If you discover a heavily corked bottle (TCA failure), retain the bottle and cork minimum 75% full. Contact us immediately to arrange a retrieval and swap protocol.</p>
        </section>

        <section>
            <h2 class="text-xs uppercase font-black tracking-widest mb-4">4. Restocking Fees</h2>
            <p class="text-gray-600">In the incredibly rare event a discretionary return is authorized by a manager due to a system fault, a baseline 15% restocking fee will be subtracted from the final refunded vector to account for transit loss.</p>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
