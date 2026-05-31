<?php
use App\Admin\Repositories\FeedbackRepository;

$feedbackRepo = new FeedbackRepository(\App\Core\Database::getPdo());
$testimonials = $feedbackRepo->getTopTestimonials(3);

if (empty($testimonials)) {
    $testimonials = [
        ['rating' => 5, 'comment' => "The selection is incredible. Found a rare whisky I'd been searching for years.", 'user_name' => "James Mitchell", 'city' => "London", 'country' => "UK"],
        ['rating' => 5, 'comment' => "Best online liquor store I've used. The customer service is exceptional.", 'user_name' => "Sarah Chen", 'city' => "New York", 'country' => "USA"],
        ['rating' => 5, 'comment' => "Royal Beverages has become my go-to. Premium quality and always reliable.", 'user_name' => "Rajesh Patel", 'city' => "Dubai", 'country' => "UAE"],
    ];
}

function getInitials($name) {
    if (!$name) return 'R';
    $parts = explode(' ', $name);
    return count($parts) >= 2 ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1)) : strtoupper(substr($name, 0, 2));
}
?>

<section class="section max-w-[1440px] mx-auto px-8 py-32">
    <div class="flex flex-col items-center mb-20 text-center">
        <span class="text-[10px] uppercase tracking-[0.4em] text-black font-black mb-4">The Verdict</span>
        <h2 class="text-3xl font-heading uppercase tracking-widest font-extrabold mb-6">Concierge Reviews</h2>
        <p class="text-gray-400 text-sm font-light italic max-w-lg">Insights from our most distinguished global clientele.</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-12 mb-24">
        <?php foreach ($testimonials as $item): ?>
        <div class="flex flex-col items-center p-12 bg-gray-50/50 border border-gray-100 group hover:bg-white hover:shadow-3xl transition-all duration-700 text-center">
            <div class="flex justify-center text-black mb-10 text-xs">
                <?php for($i=0; $i<5; $i++): ?>
                    <span class="<?= $i < $item['rating'] ? 'text-black' : 'text-gray-100' ?>">★</span>
                <?php endfor; ?>
            </div>
            <p class="text-lg text-black italic font-light leading-relaxed mb-12">"<?= htmlspecialchars($item['comment'] ?? '') ?>"</p>
            <div class="flex flex-col items-center mt-auto">
                <div class="w-12 h-12 bg-black text-white flex items-center justify-center font-bold text-[10px] uppercase mb-4 shadow-xl">
                    <?= getInitials($item['user_name'] ?? '') ?>
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] font-black uppercase tracking-[0.4em] text-black mb-1"><?= htmlspecialchars($item['user_name'] ?? '') ?></span>
                    <span class="text-[9px] text-gray-400 font-bold tracking-[0.2em] italic">
                        <?= htmlspecialchars($item['city'] ?? '') ?><?= (!empty($item['city']) && !empty($item['country'])) ? ', ' : '' ?><?= htmlspecialchars($item['country'] ?? '') ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- View All button -->
    <div class="flex justify-center mb-24">
        <a href="<?= BASE_URL ?>feedback.php" class="btn-premium px-12 group">
            <span class="relative z-10">View All Critique</span>
            <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </a>
    </div>

    <!-- Stats Ledger -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 py-16 border-t border-gray-100">
        <div class="text-center group">
            <span class="block text-4xl font-heading text-black font-black mb-2 transition-transform group-hover:scale-110">15K+</span>
            <span class="text-base text-black font-heading font-medium tracking-wider">Satisfied Clients</span>
        </div>
        <div class="text-center group">
            <span class="block text-4xl font-heading text-black font-black mb-2 transition-transform group-hover:scale-110">4.9</span>
            <span class="text-base text-black font-heading font-medium tracking-wider">Average Rating</span>
        </div>
        <div class="text-center group">
            <span class="block text-4xl font-heading text-black font-black mb-2 transition-transform group-hover:scale-110">500+</span>
            <span class="text-base text-black font-heading font-medium tracking-wider">Verified Vintages</span>
        </div>
    </div>
</section>
