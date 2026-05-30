import { isInWishlist } from './wishlist-storage.js';

export const fixImagePath = (url) => {
    if (!url) return window.ROYAL_CONFIG.ASSET_URL + 'images/placeholder-product.webp';
    if (url.includes('products/')) {
        const filename = url.split('/').pop();
        return window.ROYAL_CONFIG.ASSET_URL + 'images/' + filename;
    }
    return window.ROYAL_CONFIG.ASSET_URL + 'images/' + url.split('/').pop();
};

export const renderProductCard = (p) => {
    const price = (p.price_cents / 100).toFixed(2);
    const inStock = p.available_stock > 0;
    const isPremium = p.price_cents >= 10000;
    
    let badgeHtml = '';
    if (isPremium) badgeHtml = 'Vintage';
    else if (p.available_stock < 20 && inStock) badgeHtml = `Low Stock: ${p.available_stock}`;

    return `
        <div class="group w-full bg-white border border-gray-100 p-8 flex flex-col relative overflow-hidden transition-all duration-500 hover:border-black ${!inStock ? 'opacity-40 grayscale' : ''}" data-id="${p.id}">
            <!-- Badges -->
            <div class="absolute top-6 left-6 z-10 flex flex-col gap-2">
                ${!inStock ? `<span class="bg-gray-100 text-gray-500 text-[8px] font-black uppercase tracking-widest px-3 py-1">Depleted</span>` : ''}
                ${badgeHtml ? `<span class="bg-black text-white text-[8px] font-black uppercase tracking-widest px-3 py-1 shadow-sm">${badgeHtml}</span>` : ''}
            </div>

            <!-- Image -->
            <a href="product.php?id=${p.id}" class="block h-56 mb-8 mt-4 relative flex items-center justify-center cursor-pointer">
                <img src="${fixImagePath(p.image_url)}" 
                     alt="${p.name}" 
                     class="max-h-full max-w-full object-contain transition-transform duration-700 group-hover:scale-110 drop-shadow-2xl" 
                     loading="lazy"
                     onerror="this.src='${window.ROYAL_CONFIG.ASSET_URL}images/placeholder-product.webp'">
            </a>

            <!-- Meta -->
            <div class="text-center flex flex-col flex-grow items-center justify-end w-full">
                <span class="text-[9px] uppercase font-black tracking-[0.3em] text-gray-400 mb-2 truncate max-w-full block">
                    ${p.category_name || 'Spirit'}
                </span>
                <h3 class="text-sm font-heading uppercase tracking-widest mb-4 group-hover:text-gold transition-colors line-clamp-2 px-2">
                    ${p.name}
                </h3>
                <span class="text-xs font-black tracking-widest mb-8 uppercase">Rs. ${price}</span>
                
                <!-- Action Buttons -->
                <div class="flex gap-2 w-full mt-auto">
                    <a href="product.php?id=${p.id}" class="btn-premium-outline flex-grow h-12 text-[9px] flex items-center justify-center" style="padding: 0 0.5rem;">View Details</a>
                    <button class="btn-premium-outline w-12 h-12 flex-shrink-0 flex items-center justify-center btn-add-wishlist hover:bg-red-50 hover:text-red-600 transition-colors" style="padding: 0;" data-id="${p.id}" title="Add to Wishlist">
                        <svg class="w-4 h-4" fill="${isInWishlist(p.id) ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    </button>
                    <button class="btn-premium w-12 h-12 flex-shrink-0 flex items-center justify-center btn-add-cart" style="padding: 0;" data-id="${p.id}" ${!inStock ? 'disabled' : ''} title="Add to Cart">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </button>
                </div>
            </div>
        </div>
    `;
};

export const initCustomDropdowns = () => {
    // Find all native selects that are not inside an admin container and haven't been initialized
    const selects = document.querySelectorAll('select:not([data-custom-dropdown="initialized"])');
    
    selects.forEach(select => {
        // Skip admin selects
        if (select.closest('.admin-container') || select.closest('.admin-dashboard') || select.classList.contains('select')) return;

        select.setAttribute('data-custom-dropdown', 'initialized');
        select.style.display = 'none'; // Hide native select

        const wrapper = document.createElement('div');
        wrapper.className = 'relative inline-block w-full text-left custom-dropdown-wrapper z-40';

        const button = document.createElement('button');
        button.type = 'button';
        // Match the theme: Light mode, uppercase, bold tracking, border
        button.className = 'w-full h-12 bg-white border border-black flex items-center justify-between px-4 text-[10px] font-black uppercase tracking-widest cursor-pointer hover:bg-gray-50 transition-colors focus:outline-none';
        
        const textSpan = document.createElement('span');
        textSpan.className = 'truncate pointer-events-none';
        textSpan.textContent = select.options[select.selectedIndex]?.text || 'Select Option';

        const iconSvg = document.createElement('div');
        iconSvg.innerHTML = '<svg class="w-4 h-4 pointer-events-none transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>';
        
        button.appendChild(textSpan);
        button.appendChild(iconSvg.firstChild);
        
        const dropdownList = document.createElement('ul');
        dropdownList.className = 'absolute left-0 w-full mt-1 bg-white border border-black shadow-xl hidden flex-col max-h-60 overflow-y-auto z-50';
        
        Array.from(select.options).forEach(option => {
            const li = document.createElement('li');
            li.className = 'px-4 py-3 text-[10px] font-bold uppercase tracking-widest cursor-pointer hover:bg-gray-100 transition-colors border-b border-gray-100 last:border-0';
            li.textContent = option.text;
            li.dataset.value = option.value;
            
            if (option.selected) {
                li.classList.add('bg-gray-50', 'text-black');
            } else {
                li.classList.add('text-gray-600');
            }

            li.addEventListener('click', () => {
                select.value = option.value;
                textSpan.textContent = option.text;
                
                // Update active state in list
                Array.from(dropdownList.children).forEach(child => {
                    child.classList.remove('bg-gray-50', 'text-black');
                    child.classList.add('text-gray-600');
                });
                li.classList.add('bg-gray-50', 'text-black');
                li.classList.remove('text-gray-600');
                
                // Close dropdown
                dropdownList.classList.add('hidden');
                button.querySelector('svg').classList.remove('rotate-180');
                
                // Trigger change event for original logic
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
            dropdownList.appendChild(li);
        });

        button.addEventListener('click', (e) => {
            e.stopPropagation();
            const isHidden = dropdownList.classList.contains('hidden');
            
            // Close all other dropdowns
            document.querySelectorAll('.custom-dropdown-wrapper ul').forEach(ul => {
                ul.classList.add('hidden');
                ul.previousElementSibling?.querySelector('svg')?.classList.remove('rotate-180');
            });

            if (isHidden) {
                dropdownList.classList.remove('hidden');
                button.querySelector('svg').classList.add('rotate-180');
            }
        });

        wrapper.appendChild(button);
        wrapper.appendChild(dropdownList);
        
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select); // move original select inside wrapper
    });

    // Close dropdowns when clicking outside
    // Only attach listener once
    if (!window.__customDropdownListenerAttached) {
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.custom-dropdown-wrapper')) {
                document.querySelectorAll('.custom-dropdown-wrapper ul').forEach(ul => {
                    ul.classList.add('hidden');
                    ul.previousElementSibling?.querySelector('svg')?.classList.remove('rotate-180');
                });
            }
        });
        window.__customDropdownListenerAttached = true;
    }
};

export const showErrorBoundary = (message = "An unexpected error occurred.") => {
    const errorHtml = `
        <div style="min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; background: white; text-align: center; padding: 2rem; font-family: 'Inter', sans-serif;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">⚠</div>
            <h1 style="font-size: 1.5rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 1rem;">System Exception</h1>
            <p style="color: #666; margin-bottom: 2rem; max-width: 400px;">${message}</p>
            <a href="${window.ROYAL_CONFIG?.BASE_URL || '/'}" style="display: inline-flex; align-items: center; justify-content: center; height: 3.5rem; padding: 0 3rem; background: black; color: white; text-decoration: none; font-weight: 900; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.2em; border: 1px solid black; cursor: pointer;">Return to Home</a>
        </div>
    `;
    
    // Replace the main tag or body content
    const main = document.querySelector('main');
    if (main) {
        main.innerHTML = errorHtml;
    } else {
        document.body.innerHTML = errorHtml;
    }
};

window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled Promise Rejection:', event.reason);
    if (!window.location.pathname.endsWith('index.php') && !window.location.pathname.endsWith('/')) {
        showErrorBoundary(event.reason?.message || "Failed to communicate with the Royal Beverages server.");
    }
});

window.addEventListener('error', (event) => {
    console.error('Global Error:', event.error);
    if (!window.location.pathname.endsWith('index.php') && !window.location.pathname.endsWith('/')) {
        showErrorBoundary("A fatal application error occurred.");
    }
});
