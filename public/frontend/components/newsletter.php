<section class="bg-gray-50/50 py-32 border-y border-gray-100 text-center">
    <div class="px-8 md:px-16 mx-auto max-w-[1440px]">
        <div class="max-w-4xl mx-auto space-y-20">
            <div class="flex flex-col items-center">
                <span class="text-[10px] uppercase font-black tracking-[0.4em] text-black mb-6 italic block text-center">Membership</span>
                <h2 class="text-4xl md:text-6xl font-heading font-extrabold uppercase tracking-widest leading-none mb-10 text-black">Private <br>Collection</h2>
                <p class="text-gray-500 font-light italic leading-relaxed mb-12 max-w-2xl mx-auto">
                    Join our exclusive circle. Gain priority access to limited collections, private releases, and curated distillery notes.
                </p>
                <div class="flex flex-wrap justify-center gap-12">
                    <div class="flex items-center gap-4">
                        <div class="w-1 h-1 bg-black rounded-full"></div>
                        <span class="text-[10px] uppercase font-black tracking-widest text-black">Priority Allocation Access</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-1 h-1 bg-black rounded-full"></div>
                        <span class="text-[10px] uppercase font-black tracking-widest text-black">Bespoke Tasting Narratives</span>
                    </div>
                </div>
            </div>
            
            <div class="p-12 md:p-20 bg-white shadow-2xl space-y-10 relative overflow-hidden group max-w-2xl mx-auto">
                <div class="absolute top-0 right-0 p-8 opacity-5 transition-transform duration-1000 group-hover:scale-150">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"></path></svg>
                </div>
                
                <h3 class="text-xs uppercase font-black tracking-[0.3em] text-black border-b border-gray-50 pb-8 mb-8 relative z-10 text-center">Application for Membership</h3>
                
                <form id="newsletterForm" class="flex flex-col gap-8 relative z-10 transition-opacity duration-500">
                    <input type="email" id="newsletterEmail" placeholder="EMAIL@ADDRESS.COM" required 
                           class="w-full h-16 bg-gray-50 px-8 text-xs font-black uppercase tracking-widest border-none outline-none focus:bg-white transition-all text-center placeholder:text-gray-300">
                    <button type="submit" class="btn-premium h-16 w-full flex items-center justify-center gap-4 group">
                        Join Membership
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                    <p class="text-center text-[8px] uppercase font-black tracking-widest text-gray-400">Membership terms apply. Confidentiality guaranteed.</p>
                </form>
                
                <div id="newsletterSuccess" class="hidden text-center py-10 relative z-10 animate-fade-in">
                    <span class="text-6xl text-black mb-8 block italic font-heading">Confirmed</span>
                    <h3 class="text-xs uppercase font-black tracking-[0.3em] mb-4 text-black">Access Granted</h3>
                    <p class="text-gray-400 text-[10px] uppercase tracking-widest font-black leading-relaxed italic">The first chronicle has been dispatched to your terminal.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('newsletterForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    this.classList.add('opacity-0', 'pointer-events-none');
    setTimeout(() => {
        this.style.display = 'none';
        document.getElementById('newsletterSuccess').classList.remove('hidden');
    }, 500);
});
</script>
