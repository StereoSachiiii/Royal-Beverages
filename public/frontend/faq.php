<?php 
$pageName = 'faq';
$pageTitle = 'FAQ - Royal Beverages';
require_once __DIR__ . "/components/header.php"; 
?>

<main class="faq-page">
    <!-- Hero Section -->
    <section class="faq-hero">
        <div class="container">
            <h1 class="faq-title">Frequently Asked Questions</h1>
            <p class="faq-tagline">Find answers to common questions about ordering, delivery, and more.</p>
            
            <!-- Search Bar -->
            <div class="faq-search">
                <input type="text" id="faqSearch" class="input" placeholder="Search for answers...">
                <button class="btn-premium px-8" id="faqSearchBtn">Search</button>
            </div>
        </div>
    </section>

    <!-- FAQ Content -->
    <section class="faq-content container">
        <!-- Category Tabs -->
        <div class="faq-tabs">
            <button class="faq-tab active" data-category="all">All</button>
            <button class="faq-tab" data-category="ordering">Ordering</button>
            <button class="faq-tab" data-category="delivery">Delivery</button>
            <button class="faq-tab" data-category="payment">Payment</button>
            <button class="faq-tab" data-category="returns">Returns</button>
            <button class="faq-tab" data-category="account">Account</button>
        </div>

        <!-- FAQ Items -->
        <div class="faq-list" id="faqList">
            <!-- Ordering -->
            <div class="faq-item card" data-category="ordering">
                <button class="faq-question">
                    <span>How do I place an order?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Browse our collection, add items to your cart, and proceed to checkout. You can pay with credit/debit cards, bank transfer, or cash on delivery for eligible orders.</p>
                </div>
            </div>

            <div class="faq-item card" data-category="ordering">
                <button class="faq-question">
                    <span>Is there a minimum order amount?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>There's no minimum order amount. However, orders over Rs. 10,000 qualify for free delivery within Colombo.</p>
                </div>
            </div>

            <div class="faq-item card" data-category="ordering">
                <button class="faq-question">
                    <span>Can I modify my order after placing it?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>You can modify or cancel your order within 30 minutes of placing it. After that, please contact our support team for assistance.</p>
                </div>
            </div>

            <!-- Delivery -->
            <div class="faq-item card" data-category="delivery">
                <button class="faq-question">
                    <span>What are your delivery areas?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>We deliver island-wide across Sri Lanka. Colombo and suburbs typically receive same-day or next-day delivery. Other areas may take 2-5 business days.</p>
                </div>
            </div>

            <div class="faq-item card" data-category="delivery">
                <button class="faq-question">
                    <span>How much does delivery cost?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Delivery within Colombo costs Rs. 350. Orders over Rs. 10,000 qualify for free delivery. Other areas have varying rates based on location.</p>
                </div>
            </div>

            <div class="faq-item card" data-category="delivery">
                <button class="faq-question">
                    <span>Can I track my order?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Yes! Once your order is dispatched, you'll receive tracking information via SMS and email. You can also check your order status in your account dashboard.</p>
                </div>
            </div>

            <!-- Payment -->
            <div class="faq-item card" data-category="payment">
                <button class="faq-question">
                    <span>What payment methods do you accept?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>We accept Visa, Mastercard, American Express, bank transfers, and cash on delivery (for orders under Rs. 25,000).</p>
                </div>
            </div>

            <div class="faq-item card" data-category="payment">
                <button class="faq-question">
                    <span>Is my payment information secure?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Absolutely. We use PayHere, Sri Lanka's leading payment gateway, with bank-grade encryption. We never store your full card details.</p>
                </div>
            </div>

            <div class="faq-item card" data-category="payment">
                <button class="faq-question">
                    <span>Do you offer corporate invoicing?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Yes! For corporate orders, we can provide tax invoices and offer credit terms for registered businesses. Contact us for details.</p>
                </div>
            </div>

            <!-- Returns -->
            <div class="faq-item card" data-category="returns">
                <button class="faq-question">
                    <span>What is your return policy?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Unopened products in original packaging can be returned within 7 days for a full refund. Damaged or defective items are eligible for immediate replacement.</p>
                </div>
            </div>

            <div class="faq-item card" data-category="returns">
                <button class="faq-question">
                    <span>How do I initiate a return?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Contact our support team via the Contact page or call us. We'll arrange pickup and process your refund within 3-5 business days.</p>
                </div>
            </div>

            <!-- Account -->
            <div class="faq-item card" data-category="account">
                <button class="faq-question">
                    <span>Do I need an account to order?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>No, you can checkout as a guest. However, creating an account lets you track orders, save addresses, and earn loyalty points.</p>
                </div>
            </div>

            <div class="faq-item card" data-category="account">
                <button class="faq-question">
                    <span>I forgot my password. What do I do?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Click "Forgot Password" on the login page. We'll send a reset link to your registered email address.</p>
                </div>
            </div>

            <div class="faq-item card" data-category="account">
                <button class="faq-question">
                    <span>How do I update my account information?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Log in to your account, go to Profile Settings, and update your personal details, addresses, or password.</p>
                </div>
            </div>
        </div>

        <!-- No Results State -->
        <div class="faq-no-results hidden" id="faqNoResults">
            <div class="empty-state">
                <div class="empty-state-icon">🔍</div>
                <h3 class="empty-state-title">No Results Found</h3>
                <p class="empty-state-text">We couldn't find any FAQs matching your search. Try different keywords or browse by category.</p>
            </div>
        </div>
    </section>

    <!-- Still Need Help? -->
    <section class="faq-help">
        <div class="container text-center">
            <h2>Still Have Questions?</h2>
            <p>Our team is happy to help you with any questions.</p>
            <div class="faq-help-actions">
                <a href="<?= getPageUrl('contact') ?>" class="btn-premium px-8">Contact Us</a>
                <a href="tel:+94112345678" class="btn-premium-outline px-8">Call: +94 11 234 5678</a>
            </div>
        </div>
    </section>
</main>

<style>
/* FAQ Page Styles */
.faq-page {
    background: var(--white);
}

.faq-hero {
    background: linear-gradient(135deg, var(--black) 0%, var(--gray-800) 100%);
    color: var(--white);
    padding: 120px 0 80px;
    text-align: center;
}

.faq-title {
    font-family: var(--font-serif);
    font-size: 3rem;
    font-weight: 300;
    font-style: italic;
    margin: 0 0 var(--space-md);
}

.faq-tagline {
    color: var(--gray-400);
    font-size: 1.125rem;
    margin-bottom: var(--space-xl);
}

.faq-search {
    display: flex;
    gap: var(--space-sm);
    max-width: 500px;
    margin: 0 auto;
}

.faq-search .input {
    flex: 1;
}

.faq-content {
    padding: var(--space-3xl) 0;
}

.faq-tabs {
    display: flex;
    gap: var(--space-sm);
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: var(--space-2xl);
}

.faq-tab {
    padding: var(--space-sm) var(--space-lg);
    background: var(--gray-100);
    border: none;
    border-radius: var(--radius-full);
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--gray-600);
    cursor: pointer;
    transition: all var(--duration-fast) var(--ease-out);
}

.faq-tab:hover {
    background: var(--gray-200);
    color: var(--black);
}

.faq-tab.active {
    background: var(--black);
    color: var(--white);
}

.faq-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-md);
    max-width: 800px;
    margin: 0 auto;
}

.faq-item {
    overflow: hidden;
}

.faq-item.hidden {
    display: none;
}

.faq-question {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    padding: var(--space-lg);
    background: none;
    border: none;
    text-align: left;
    font-size: 1rem;
    font-weight: 600;
    color: var(--black);
    cursor: pointer;
    transition: background var(--duration-fast) var(--ease-out);
}

.faq-question:hover {
    background: var(--gray-50);
}

.faq-icon {
    font-size: 1.5rem;
    font-weight: 300;
    color: var(--gold);
    transition: transform var(--duration-fast) var(--ease-out);
}

.faq-item.open .faq-icon {
    transform: rotate(45deg);
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height var(--duration-normal) var(--ease-out);
}

.faq-item.open .faq-answer {
    max-height: 500px;
}

.faq-answer p {
    padding: 0 var(--space-lg) var(--space-lg);
    color: var(--gray-600);
    line-height: 1.7;
}

.faq-no-results {
    padding: var(--space-3xl) 0;
}

.faq-help {
    background: var(--gray-50);
    padding: var(--space-3xl) 0;
}

.faq-help h2 {
    font-family: var(--font-serif);
    font-size: 2rem;
    margin-bottom: var(--space-sm);
}

.faq-help p {
    color: var(--gray-500);
    margin-bottom: var(--space-xl);
}

.faq-help-actions {
    display: flex;
    gap: var(--space-md);
    justify-content: center;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .faq-title { font-size: 2rem; }
    .faq-search { flex-direction: column; }
    .faq-tabs { gap: var(--space-xs); }
    .faq-tab { padding: var(--space-xs) var(--space-md); font-size: 0.85rem; }
}
</style>

<script>
// FAQ Functionality
document.addEventListener('DOMContentLoaded', () => {
    const faqItems = document.querySelectorAll('.faq-item');
    const tabs = document.querySelectorAll('.faq-tab');
    const searchInput = document.getElementById('faqSearch');
    const noResults = document.getElementById('faqNoResults');
    const faqList = document.getElementById('faqList');

    // Toggle FAQ item
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        question.addEventListener('click', () => {
            // Close others
            faqItems.forEach(other => {
                if (other !== item) other.classList.remove('open');
            });
            // Toggle current
            item.classList.toggle('open');
        });
    });

    // Category tabs
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const category = tab.dataset.category;
            
            // Update active tab
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            // Filter items
            let visibleCount = 0;
            faqItems.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.classList.remove('hidden');
                    visibleCount++;
                } else {
                    item.classList.add('hidden');
                }
            });
            
            // Show/hide no results
            noResults.classList.toggle('hidden', visibleCount > 0);
        });
    });

    // Search functionality
    const filterBySearch = () => {
        const term = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;
        
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question span').textContent.toLowerCase();
            const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
            
            if (question.includes(term) || answer.includes(term)) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });
        
        // Reset tabs
        tabs.forEach(t => t.classList.remove('active'));
        tabs[0].classList.add('active');
        
        // Show/hide no results
        noResults.classList.toggle('hidden', visibleCount > 0);
    };

    searchInput.addEventListener('input', filterBySearch);
    document.getElementById('faqSearchBtn').addEventListener('click', filterBySearch);
});
</script>

<?php require_once __DIR__ . "/components/footer.php"; ?>
