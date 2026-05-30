<?php 
$pageName = 'contact';
$pageTitle = 'Contact Us - Royal Beverages';
require_once __DIR__ . "/components/header.php"; 
?>

<main class="min-h-screen pt-20 bg-white">
    <!-- Hero Section (Clean White) -->
    <section class="py-20 bg-white border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-8">
            <h1 class="text-5xl md:text-6xl font-black uppercase tracking-tightest mb-4 text-black">
                Get in Touch
            </h1>
            <p class="text-[11px] font-bold uppercase tracking-widest text-gray-500 max-w-lg leading-relaxed">
                Our team is available to assist with inquiries ranging from order support to private spirit procurement.
            </p>
        </div>
    </section>

    <!-- Main Content Grid -->
    <section class="max-w-7xl mx-auto px-8 py-20">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-20">
            
            <!-- Contact Form -->
            <div id="contactFormWrapper" class="lg:col-span-7 bg-white">
                <div class="mb-12">
                    <h2 class="text-2xl font-black uppercase tracking-tightest text-black">Send a Message</h2>
                </div>

                <form id="contactForm" class="space-y-10">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <div class="space-y-3">
                            <label for="contactName" class="text-[10px] font-black uppercase tracking-widest text-black">Full Name</label>
                            <input type="text" id="contactName" name="name" 
                                class="w-full bg-white border-0 border-b-2 border-gray-100 focus:border-black focus:ring-0 px-0 py-4 text-[11px] font-bold uppercase tracking-widest transition-all placeholder:text-gray-200"
                                placeholder="Your Name">
                        </div>
                        
                        <div class="space-y-3">
                            <label for="contactEmail" class="text-[10px] font-black uppercase tracking-widest text-black">Email Address</label>
                            <input type="email" id="contactEmail" name="email" 
                                class="w-full bg-white border-0 border-b-2 border-gray-100 focus:border-black focus:ring-0 px-0 py-4 text-[11px] font-bold uppercase tracking-widest transition-all placeholder:text-gray-200"
                                placeholder="email@example.com">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <div class="space-y-3">
                            <label for="contactPhone" class="text-[10px] font-black uppercase tracking-widest text-black">Phone Number</label>
                            <input type="tel" id="contactPhone" name="phone" 
                                class="w-full bg-white border-0 border-b-2 border-gray-100 focus:border-black focus:ring-0 px-0 py-4 text-[11px] font-bold uppercase tracking-widest transition-all placeholder:text-gray-200"
                                placeholder="+94 XX XXX XXXX">
                        </div>
                        
                        <div class="space-y-3">
                            <label for="contactSubject" class="text-[10px] font-black uppercase tracking-widest text-black">Subject</label>
                            <select id="contactSubject" name="subject" 
                                class="w-full bg-white border-0 border-b-2 border-gray-100 focus:border-black focus:ring-0 px-0 py-4 text-[11px] font-bold uppercase tracking-widest transition-all appearance-none cursor-pointer">
                                <option value="">Select a Topic</option>
                                <option value="general">General Inquiry</option>
                                <option value="order">Order Support</option>
                                <option value="product">Product Information</option>
                                <option value="corporate">Corporate & Bulk</option>
                                <option value="feedback">Feedback</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label for="contactMessage" class="text-[10px] font-black uppercase tracking-widest text-black">How can we help you?</label>
                        <textarea id="contactMessage" name="message" rows="5"
                            class="w-full bg-white border-0 border-b-2 border-gray-100 focus:border-black focus:ring-0 px-0 py-4 text-[11px] font-bold uppercase tracking-widest transition-all placeholder:text-gray-200 resize-none"
                            placeholder="Enter your message..."></textarea>
                    </div>

                    <div class="pt-4">
                        <button type="submit" id="contactSubmit" 
                            class="inline-flex items-center justify-center px-10 py-4 bg-black text-white text-[10px] font-black uppercase tracking-widest hover:bg-white hover:text-black border-2 border-black transition-all">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sidebar Info -->
            <div class="lg:col-span-5 space-y-16">
                <!-- Location -->
                <div>
                    <span class="text-[9px] font-black uppercase tracking-widest text-gray-400 block mb-6">Visit Us</span>
                    <h3 class="text-xl font-black uppercase tracking-tightest mb-4">Our Flagship Store</h3>
                    <p class="text-[10px] font-bold uppercase tracking-widest leading-loose text-gray-500 mb-6">
                        Royal Beverages Complex<br/>
                        123 Galle Road, Colombo 03<br/>
                        Sri Lanka
                    </p>
                    <a href="https://maps.google.com" target="_blank" class="text-[9px] font-black uppercase tracking-widest border-b border-black pb-1 hover:text-gray-400 transition-all">
                        View on Maps
                    </a>
                </div>

                <!-- Contact Details -->
                <div class="pt-10 border-t border-gray-100">
                    <span class="text-[9px] font-black uppercase tracking-widest text-gray-400 block mb-6">Contact Info</span>
                    <div class="space-y-8">
                        <div>
                            <span class="text-[8px] font-black uppercase text-gray-400 block mb-1">Email</span>
                            <span class="text-[10px] font-bold tracking-widest text-black">info@royalliquor.lk</span>
                        </div>
                        <div>
                            <span class="text-[8px] font-black uppercase text-gray-400 block mb-1">Phone</span>
                            <span class="text-[10px] font-bold tracking-widest text-black">0702214096</span>
                        </div>
                    </div>
                </div>

                <!-- Socials -->
                <div class="pt-10 border-t border-gray-100">
                    <span class="text-[9px] font-black uppercase tracking-widest text-gray-400 block mb-6">Follow Us</span>
                    <div class="flex gap-6">
                        <a href="#" class="text-[9px] font-black uppercase tracking-widest text-black hover:text-gray-400 transition-colors">Instagram</a>
                        <a href="#" class="text-[9px] font-black uppercase tracking-widest text-black hover:text-gray-400 transition-colors">Facebook</a>
                        <a href="#" class="text-[9px] font-black uppercase tracking-widest text-black hover:text-gray-400 transition-colors">Twitter</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Simple Map (Clean Border) -->
    <section class="h-[50vh] border-y border-gray-100 bg-gray-50">
        <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.798467128636!2d79.84871987499715!3d6.914744018383573!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae2596a04578f77%3A0x39e524f3c56a9f6a!2sGalle%20Rd%2C%20Colombo%2C%20Sri%20Lanka!5e0!3m2!1sen!2s!4v1702432000000!5m2!1sen!2s"
            class="w-full h-full border-0 grayscale opacity-80" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </section>

    <!-- FAQ Section -->
    <section class="max-w-7xl mx-auto px-8 py-24">
        <div class="mb-16">
            <h2 class="text-3xl font-black uppercase tracking-tightest">Frequently Asked Questions</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-16 gap-y-12">
            <div>
                <h3 class="text-[10px] font-black uppercase tracking-widest text-black mb-3">What are your delivery areas?</h3>
                <p class="text-[11px] text-gray-500 font-bold uppercase tracking-widest leading-loose">
                    We deliver island-wide across Sri Lanka. Colombo orders are typically delivered within 24 hours.
                </p>
            </div>
            <div>
                <h3 class="text-[10px] font-black uppercase tracking-widest text-black mb-3">Do you offer corporate orders?</h3>
                <p class="text-[11px] text-gray-500 font-bold uppercase tracking-widest leading-loose">
                    Yes, we offer special pricing for bulk and corporate orders. Please contact our team for a custom quote.
                </p>
            </div>
            <div>
                <h3 class="text-[10px] font-black uppercase tracking-widest text-black mb-3">How can I pay for my order?</h3>
                <p class="text-[11px] text-gray-500 font-bold uppercase tracking-widest leading-loose">
                    We accept credit/debit cards, bank transfers, and cash on delivery for eligible locations.
                </p>
            </div>
            <div>
                <h3 class="text-[10px] font-black uppercase tracking-widest text-black mb-3">Can I return a product?</h3>
                <p class="text-[11px] text-gray-500 font-bold uppercase tracking-widest leading-loose">
                    Unopened products can be returned within 7 days. Please contact us for further details.
                </p>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . "/components/footer.php"; ?>

<script type="module">
import { initCustomDropdowns } from '<?= ASSET_URL ?>js/ui-components.js?v=<?= time() ?>';
document.addEventListener('DOMContentLoaded', initCustomDropdowns);
</script>
