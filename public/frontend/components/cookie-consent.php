<!-- Cookie Consent Modal -->
<div id="cookieModalBg" class="fixed inset-x-0 bottom-0 z-[2000] p-6 pointer-events-none transform translate-y-full transition-transform duration-1000">
    <div class="max-w-4xl mx-auto bg-white border border-gray-100 shadow-[0_-20px_50px_-15px_rgba(0,0,0,0.1)] p-8 md:p-12 pointer-events-auto flex flex-col md:flex-row items-center gap-8 text-center md:text-left">
        <div class="flex-grow space-y-2 text-center md:text-left">
            <span class="text-[9px] uppercase font-black tracking-[0.4em] text-gray-400 block mb-2 italic">Privacy & Experience</span>
            <p class="text-xs text-black font-light leading-relaxed max-w-2xl">
                We use cookies to personalize your shopping experience, analyze site traffic, and recommend spirits tailored to your taste. 
                By clicking "Accept", you agree to our use of cookies.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row gap-4 shrink-0 justify-center">
            <button id="cookieReject" class="btn-premium-outline px-10 h-14 !text-gray-400 border-gray-100 hover:border-black hover:text-black">Decline</button>
            <button id="cookieAccept" class="btn-premium px-10 h-14">Accept</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('cookieModalBg');
    const accept = document.getElementById('cookieAccept');
    const reject = document.getElementById('cookieReject');

    if (!localStorage.getItem('cookieConsent')) {
        setTimeout(() => {
            modal.classList.remove('translate-y-full');
        }, 2000);
    }

    const dismiss = () => {
        modal.classList.add('translate-y-full');
        localStorage.setItem('cookieConsent', 'true');
    };

    accept.addEventListener('click', dismiss);
    reject.addEventListener('click', dismiss);
});
</script>
