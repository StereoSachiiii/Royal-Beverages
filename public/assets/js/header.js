import { cart } from "./cart-service.js";
import { initScrollReveal } from "./scroll-reveal.js";


export const updateCartCount = (count = null) => {
    const cartCount = document.querySelector('.count');
    const cartCountHeader = document.querySelector('.count-display');
    const finalCount = count !== null ? count : cart.getCount();

    if (cartCount) {
        cartCount.textContent = finalCount || '';
        if (!finalCount || finalCount == 0) cartCount.classList.add('hidden');
        else cartCount.classList.remove('hidden');
    }
    
    if (cartCountHeader) {
        cartCountHeader.textContent = finalCount || '';
        if (!finalCount || finalCount == 0) {
            cartCountHeader.classList.add('hidden');
            cartCountHeader.classList.remove('flex');
        } else {
            cartCountHeader.classList.remove('hidden');
            cartCountHeader.classList.add('flex');
        }
    }
};

// Listen for cart changes globally
window.addEventListener('cart:updated', (e) => {
    updateCartCount(e.detail.count);
});


document.addEventListener("DOMContentLoaded", async () => {
    const menuBtn = document.getElementById('menu');
    const mobileSidebar = document.getElementById('mobileSidebar');
    const mobileClose = document.getElementById('mobileClose');

    const profileBtn = document.getElementById('profile');
    const profileSidebar = document.getElementById('profileSidebar');
    const profileClose = document.getElementById('profileClose');

    const sidebarOverlay = document.getElementById('sidebarOverlay');

    const searchBtn = document.getElementById('searchBtn');
    const searchWrapper = document.querySelector('.search-wrapper');
    const searchInput = document.getElementById('searchInput');
    const searchCloseBtn = document.getElementById('searchCloseBtn');

    const cartIcon = document.getElementById('cart');
    const cartExpand = document.querySelector('.cart-expand');

    const cookieModalBg = document.getElementById('cookieModalBg');
    const cookieAccept = document.getElementById('cookieAccept');
    const cookieReject = document.getElementById('cookieReject');

    await updateCartCount();


    const openMobileSidebar = () => {
        const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
        mobileSidebar.classList.remove('-translate-x-full');
        mobileSidebar.classList.add('translate-x-0');
        sidebarOverlay.classList.remove('opacity-0', 'invisible');
        sidebarOverlay.classList.add('opacity-100', 'visible');
        document.body.style.paddingRight = `${scrollbarWidth}px`;
        document.body.style.overflow = 'hidden';
    };

    const closeMobileSidebar = () => {
        mobileSidebar.classList.remove('translate-x-0');
        mobileSidebar.classList.add('-translate-x-full');
        if (!profileSidebar.classList.contains('translate-x-0')) {
            sidebarOverlay.classList.add('opacity-0', 'invisible');
            sidebarOverlay.classList.remove('opacity-100', 'visible');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }
    };

    if (menuBtn) {
        menuBtn.addEventListener('click', openMobileSidebar);
    }

    if (mobileClose) {
        mobileClose.addEventListener('click', closeMobileSidebar);
    }


    const openProfileSidebar = () => {
        const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
        profileSidebar.classList.remove('translate-x-full');
        profileSidebar.classList.add('translate-x-0');
        sidebarOverlay.classList.remove('opacity-0', 'invisible');
        sidebarOverlay.classList.add('opacity-100', 'visible');
        document.body.style.paddingRight = `${scrollbarWidth}px`;
        document.body.style.overflow = 'hidden';
    };

    const closeProfileSidebar = () => {
        profileSidebar.classList.remove('translate-x-0');
        profileSidebar.classList.add('translate-x-full');
        if (!mobileSidebar.classList.contains('translate-x-0')) {
            sidebarOverlay.classList.add('opacity-0', 'invisible');
            sidebarOverlay.classList.remove('opacity-100', 'visible');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }
    };

    if (profileBtn) {
        profileBtn.addEventListener('click', openProfileSidebar);
    }

    if (profileClose) {
        profileClose.addEventListener('click', closeProfileSidebar);
    }


    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            closeMobileSidebar();
            closeProfileSidebar();
        });
    }


    const expandSearch = () => {
        searchWrapper.classList.add('expanded');
        setTimeout(() => searchInput.focus(), 250);
    };

    const collapseSearch = () => {
        searchWrapper.classList.remove('expanded');
        searchInput.value = '';
        searchInput.blur();
    };

    if (searchBtn) {
        searchBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (searchWrapper.classList.contains('expanded')) {
                if (searchInput.value.trim()) {
                    console.log('Search for:', searchInput.value);
                } else {
                    collapseSearch();
                }
            } else {
                expandSearch();
            }
        });
    }

    if (searchCloseBtn) {
        searchCloseBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            collapseSearch();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && searchInput.value.trim()) {
                console.log('Search for:', searchInput.value);
            }
        });
    }

    document.addEventListener('click', (e) => {
        if (searchWrapper.classList.contains('expanded')) {
            const isClickInside = searchWrapper.contains(e.target);
            if (!isClickInside) {
                collapseSearch();
            }
        }
    });

    if (cartIcon && cartExpand) {
        cartIcon.addEventListener('click', (e) => {
            if (e.target.closest('svg') || e.target.closest('.count-display')) {
                e.preventDefault();
                e.stopPropagation();
                cartExpand.classList.toggle('active');
            }
        });

        document.addEventListener('click', (e) => {
            const isClickInside = cartIcon.contains(e.target) || cartExpand.contains(e.target);
            if (cartExpand.classList.contains('active') && !isClickInside) {
                cartExpand.classList.remove('active');
            }
        });
    }


    const cookieConsent = localStorage.getItem('cookieConsent');

    if (!cookieConsent && cookieModalBg) {
        setTimeout(() => {
            cookieModalBg.classList.remove('opacity-0', 'invisible');
            cookieModalBg.classList.add('opacity-100', 'visible');
        }, 1000);
    }

    if (cookieAccept) {
        cookieAccept.addEventListener('click', () => {
            localStorage.setItem('cookieConsent', 'accepted');
            cookieModalBg.classList.add('opacity-0', 'invisible');
            cookieModalBg.classList.remove('opacity-100', 'visible');
        });
    }

    if (cookieReject) {
        cookieReject.addEventListener('click', () => {
            localStorage.setItem('cookieConsent', 'rejected');
            cookieModalBg.classList.add('opacity-0', 'invisible');
            cookieModalBg.classList.remove('opacity-100', 'visible');
        });
    }


    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeMobileSidebar();
            closeProfileSidebar();
            collapseSearch();
            if (cartExpand) cartExpand.classList.remove('active');
        }
    });
});
