<?php require_once __DIR__ . "/../../config/urls.php"; ?>

<div class="relative w-full h-[100vh] min-h-[700px] overflow-hidden bg-black">
    <div class="relative w-full h-full" id="mainSlider">
        <!-- Slide 1: The Art of Whiskey -->
        <div class="absolute inset-0 active duration-1000 transition-opacity" style="opacity: 1; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);">
            <div class="absolute inset-0 bg-black/40 z-10"></div>
            <img src="<?= ASSET_URL ?>images/slider-1.webp" class="w-full h-full object-cover scale-110 transition-transform duration-[10000ms] ease-out slide-img" loading="eager">
            <div class="absolute inset-0 z-20 flex items-center justify-center">
                <div class="text-center px-8 max-w-5xl">
                    <span class="text-white text-xs uppercase tracking-[0.5em] font-black mb-8 block animate-fade-in opacity-80">Premium Collection</span>
                    <h1 class="text-5xl md:text-8xl font-heading font-extrabold text-white uppercase tracking-widest mb-10 leading-none slide-title">Master <br>The Craft</h1>
                    <p class="text-xl text-gray-300 mb-12 tracking-wide font-light italic max-w-2xl mx-auto opacity-0 animate-fade-in-delay">The finest scotch, curated for the modern connoisseur. A legacy of excellence.</p>
                    <div class="flex flex-col sm:flex-row gap-6 justify-center opacity-0 animate-fade-in-delay-2">
                        <a href="shop.php" class="btn-premium px-12 h-16 flex items-center bg-white text-black hover:bg-gray-100 border-none transition-all">Enter Collection</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 2: Royal Celebrations -->
        <div class="absolute inset-0 opacity-0 transition-slow" style="transition: opacity 1.5s cubic-bezier(0.4, 0, 0.2, 1);">
            <div class="absolute inset-0 bg-black/40 z-10"></div>
            <img src="<?= ASSET_URL ?>images/slider-2.webp" class="w-full h-full object-cover scale-110 transition-transform duration-[10000ms] ease-out slide-img">
            <div class="absolute inset-0 z-20 flex items-center justify-center">
                <div class="text-center px-8 max-w-5xl">
                    <span class="text-white text-xs uppercase tracking-[0.5em] font-black mb-8 block opacity-80">Exclusive Selection</span>
                    <h1 class="text-5xl md:text-8xl font-heading font-extrabold text-white uppercase tracking-widest mb-10 leading-none">Royal <br>Vintages</h1>
                    <p class="text-xl text-gray-300 mb-12 tracking-wide font-light italic max-w-2xl mx-auto">Timeless elegance in every drop. Discover our private cellar of rare harvests.</p>
                    <div class="flex flex-col sm:flex-row gap-6 justify-center">
                        <a href="shop.php?category=wine" class="btn-premium px-12 h-16 flex items-center bg-white text-black border-none">Shop Vintage</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 3: Pure Excellence -->
        <div class="absolute inset-0 opacity-0 transition-slow" style="transition: opacity 1.5s cubic-bezier(0.4, 0, 0.2, 1);">
            <div class="absolute inset-0 bg-black/40 z-10"></div>
            <img src="<?= ASSET_URL ?>images/slider-3.webp" class="w-full h-full object-cover scale-110 transition-transform duration-[10000ms] ease-out slide-img">
            <div class="absolute inset-0 z-20 flex items-center justify-center">
                <div class="text-center px-8 max-w-5xl">
                    <span class="text-white text-xs uppercase tracking-[0.5em] font-black mb-8 block opacity-80">Modern Luxury</span>
                    <h1 class="text-5xl md:text-8xl font-heading font-extrabold text-white uppercase tracking-widest mb-10 leading-none">The Spirit <br>of Gin</h1>
                    <p class="text-xl text-gray-300 mb-12 tracking-wide font-light italic max-w-2xl mx-auto">Artisanally distilled botanicals. A crystalline experience of botanic perfection.</p>
                    <div class="flex flex-col sm:flex-row gap-6 justify-center">
                        <a href="shop.php" class="btn-premium px-12 h-16 flex items-center bg-white text-black border-none">Explore Collection</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Overlay -->
    <div class="absolute bottom-12 left-1/2 -translate-x-1/2 z-30 flex flex-col items-center gap-6">
        <div class="flex gap-4" id="sliderDots">
            <button class="w-12 h-[2px] bg-white cursor-pointer transition-all duration-500 opacity-100" data-slide="0"></button>
            <button class="w-12 h-[2px] bg-white/20 cursor-pointer transition-all duration-500 hover:bg-white/50" data-slide="1"></button>
            <button class="w-12 h-[2px] bg-white/20 cursor-pointer transition-all duration-500 hover:bg-white/50" data-slide="2"></button>
        </div>
        <div class="text-[10px] uppercase font-black tracking-[0.3em] text-white/50">
            <span id="currentSlideNum">01</span> / 03
        </div>
    </div>
</div>

<style>
    .animate-fade-in { animation: fadeIn 1s forwards; }
    .animate-fade-in-delay { animation: fadeIn 1s 0.5s forwards; }
    .animate-fade-in-delay-2 { animation: fadeIn 1s 0.8s forwards; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    
    .slide-active .slide-img { transform: scale(1); }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const slides = document.querySelectorAll('#mainSlider > div');
    const dots = document.querySelectorAll('#sliderDots button');
    const numDisplay = document.getElementById('currentSlideNum');
    let currentSlide = 0;

    const showSlide = (index) => {
        slides.forEach((s, i) => {
            s.style.opacity = 0;
            s.style.zIndex = 0;
            s.classList.remove('slide-active');
            if (dots[i]) {
                dots[i].classList.replace('bg-white', 'bg-white/20');
                dots[i].classList.remove('opacity-100');
            }
        });

        slides[index].style.opacity = 1;
        slides[index].style.zIndex = 10;
        slides[index].classList.add('slide-active');
        if (dots[index]) {
            dots[index].classList.replace('bg-white/20', 'bg-white');
            dots[index].classList.add('opacity-100');
        }
        numDisplay.textContent = (index + 1).toString().padStart(2, '0');
    };

    const nextSlide = () => {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    };

    let slideInterval = setInterval(nextSlide, 8000);
    showSlide(0);

    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => {
            clearInterval(slideInterval);
            currentSlide = i;
            showSlide(currentSlide);
            slideInterval = setInterval(nextSlide, 8000);
        });
    });
});
</script>
