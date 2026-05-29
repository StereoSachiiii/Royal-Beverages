<?php
declare(strict_types=1);

$pageName = 'feedback';
$pageTitle = 'Customer Reviews - Royal Beverages';

require_once __DIR__ . '/components/header.php';

$session = \App\Core\Session::getInstance();
$isLoggedIn = $session->isLoggedIn();
$userId = $session->get('user_id');

// Use repository directly for initial load (following index page pattern)
use App\Admin\Repositories\FeedbackRepository;
$feedbackRepo = new FeedbackRepository();
$initialFeedback = $feedbackRepo->getAllPaginated(12, 0, true);
?>

<main class="min-h-screen pt-20" data-user-id="<?= $userId ?? 'null' ?>">
    <!-- Animated Hero Section -->
    <?php
    $heroTitle = 'Customer Reviews';
    $heroSubtitle = 'Customer Voice';
    $heroDescription = 'We invite our distinguished clientele to share their experiences. Your insights ensure the perpetual refinement of our curated collection.';
    $heroId = 'feedbackHero';
    $heroOffset = '15%';
    $heroBreadcrumbs = [
        ['url' => BASE_URL, 'label' => 'Home'],
        ['url' => '', 'label' => 'Customer Reviews']
    ];
    require_once __DIR__ . '/components/animated-hero.php';
    ?>

    <!-- Submission Section -->
    <section class="max-w-[1440px] mx-auto px-8 py-32 grid grid-cols-1 lg:grid-cols-12 gap-24">
        <!-- Form Column -->
        <div class="lg:col-span-5">
            <div class="sticky top-40">
                <div class="mb-16">
                    <h2 class="text-2xl font-heading uppercase tracking-widest font-extrabold mb-4">Post a Review</h2>
                    <div class="w-12 h-px bg-black"></div>
                </div>

                <?php if ($isLoggedIn): ?>
                <form id="feedbackForm" class="space-y-12">
                    <!-- Product Selection -->
                    <div class="space-y-4">
                        <label class="text-[10px] uppercase font-black tracking-[0.3em] text-gray-400">Select Product</label>
                        <div class="relative">
                            <input type="text" id="productSearch" placeholder="Search product name..."
                                class="w-full bg-transparent border-b border-gray-200 py-4 text-sm font-light tracking-wide focus:border-black outline-none transition-all placeholder:text-gray-300">
                            <div id="productResults" class="absolute top-full left-0 w-full bg-white border border-gray-100 shadow-2xl z-50 hidden max-h-[300px] overflow-y-auto">
                                <!-- Results populated by JS -->
                            </div>
                        </div>
                        <div id="selectedProductDisplay" class="mt-4">
                            <!-- Selected product shown here -->
                        </div>
                    </div>

                    <!-- Rating -->
                    <div class="space-y-6">
                        <label class="text-[10px] uppercase font-black tracking-[0.3em] text-gray-400">Rating</label>
                        <div id="starContainer" class="flex gap-4">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" class="star-btn hover:scale-110 transition-transform" data-rating="<?= $i ?>">
                                <svg class="w-8 h-8 text-gray-200 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.175 0l-3.976 2.888c-.783.57-1.838-.197-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.382-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            </button>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Comment -->
                    <div class="space-y-4">
                        <label for="comment" class="text-[10px] uppercase font-black tracking-[0.3em] text-gray-400">Your Experience</label>
                        <textarea id="comment" placeholder="Describe your experience with the notes and profile..."
                            class="w-full bg-transparent border border-gray-100 p-8 min-h-[200px] text-sm font-light leading-relaxed focus:border-black outline-none transition-all placeholder:text-gray-300 resize-none"></textarea>
                    </div>

                    <button type="button" id="submitFeedback" class="btn-premium w-full group">
                        <span class="relative z-10">Submit Review</span>
                        <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </button>
                </form>
                <?php else: ?>
                <div class="p-12 bg-gray-50 border border-gray-100 flex flex-col items-center text-center">
                    <svg class="w-12 h-12 text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <p class="text-xs uppercase font-black tracking-widest text-black mb-8">Members Only</p>
                    <p class="text-[10px] text-gray-400 tracking-widest leading-loose mb-10">Sign in to share your refined perspective with the Royal Beverages community.</p>
                    <a href="auth.php" class="btn-premium px-12">Sign In</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Feedback Thread Column -->
        <div class="lg:col-span-7" id="reviewsColumn">
            <div class="mb-16">
                <h2 class="text-2xl font-heading uppercase tracking-widest font-extrabold mb-4">The Directory</h2>
                <div class="w-12 h-px bg-black"></div>
            </div>

            <div id="feedbackList" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php if (!empty($initialFeedback)): ?>
                    <?php foreach ($initialFeedback as $item): ?>
                    <div class="p-10 bg-gray-50/50 border border-gray-100 group hover:bg-white hover:shadow-3xl transition-all duration-700">
                        <div class="flex justify-between items-start mb-8">
                            <div class="flex gap-1 text-[10px]">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <span class="<?= $i < $item['rating'] ? 'text-black' : 'text-gray-200' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <span class="text-[8px] font-black uppercase tracking-widest text-gray-300">
                                <?= date('m/d/Y', strtotime($item['created_at'])) ?>
                            </span>
                        </div>
                        
                        <p class="text-sm text-black italic font-light leading-relaxed mb-8 line-clamp-4">"<?= htmlspecialchars($item['comment']) ?>"</p>
                        
                        <div class="flex items-center gap-4 mt-auto pt-6 border-t border-gray-50">
                            <div class="w-8 h-8 bg-black text-white flex items-center justify-center font-bold text-[8px] uppercase">
                                <?= strtoupper(substr($item['user_name'] ?? 'G', 0, 2)) ?>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[9px] font-black uppercase tracking-widest text-black"><?= htmlspecialchars($item['user_name'] ?? 'Verified Guest') ?></span>
                                <span class="text-[8px] text-gray-400 font-bold tracking-widest uppercase truncate max-w-[150px]"><?= htmlspecialchars($item['product_name']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full py-20 text-center border border-dashed border-gray-100">
                        <p class="text-[10px] uppercase font-black tracking-widest text-gray-300">No reviews found in the directory.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div id="toastContainer"></div>
</main>

<style>
    @keyframes premium-slide-in {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes premium-fade {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes premium-fade-out {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(20px); }
    }
    .animate-premium-slide-in { animation: premium-slide-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .animate-premium-fade { animation: premium-fade 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .animate-premium-fade-out { animation: premium-fade-out 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>

<?php require_once __DIR__ . '/components/footer.php'; ?>
