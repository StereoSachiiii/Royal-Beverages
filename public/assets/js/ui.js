/**
 * Royal Beverages - UI Utilities
 * Reusable UI components: modals, toasts, loaders, etc.
 */

// ═══════════════════════════════════════════════════════════
// TOAST NOTIFICATIONS
// ═══════════════════════════════════════════════════════════

let toastContainer = null;

function ensureToastContainer() {
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        toastContainer.id = 'toastContainer';
        document.body.appendChild(toastContainer);
    }
    return toastContainer;
}

export function showToast(message, type = 'info', duration = 3000) {
    const container = ensureToastContainer();

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };

    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || icons.info}</span>
        <span class="toast-message">${message}</span>
        <button class="toast-close" aria-label="Close">✕</button>
    `;

    container.appendChild(toast);

    // Trigger animation
    requestAnimationFrame(() => toast.classList.add('show'));

    // Close button
    toast.querySelector('.toast-close').addEventListener('click', () => removeToast(toast));

    // Auto remove
    if (duration > 0) {
        setTimeout(() => removeToast(toast), duration);
    }

    return toast;
}

function removeToast(toast) {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
}

// Convenience methods
export const toast = {
    success: (msg, dur) => showToast(msg, 'success', dur),
    error: (msg, dur) => showToast(msg, 'error', dur),
    warning: (msg, dur) => showToast(msg, 'warning', dur),
    info: (msg, dur) => showToast(msg, 'info', dur)
};

// ═══════════════════════════════════════════════════════════
// MODAL SYSTEM
// ═══════════════════════════════════════════════════════════

const modalStack = [];

export function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return null;

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    modalStack.push(modalId);

    // Focus trap
    const focusable = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (focusable.length) focusable[0].focus();

    return modal;
}

export function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modal.classList.remove('active');

    const index = modalStack.indexOf(modalId);
    if (index > -1) modalStack.splice(index, 1);

    if (modalStack.length === 0) {
        document.body.style.overflow = '';
    }
}

export function closeAllModals() {
    modalStack.forEach(id => {
        const modal = document.getElementById(id);
        if (modal) modal.classList.remove('active');
    });
    modalStack.length = 0;
    document.body.style.overflow = '';
}

// Global ESC key handler
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalStack.length > 0) {
        closeModal(modalStack[modalStack.length - 1]);
    }
});

// ═══════════════════════════════════════════════════════════
// LOADING STATES
// ═══════════════════════════════════════════════════════════

export function showLoading(element, size = 'md') {
    if (!element) return;

    element.dataset.originalContent = element.innerHTML;
    element.dataset.loading = 'true';
    element.disabled = true;

    const spinner = document.createElement('span');
    spinner.className = `spinner spinner-${size}`;
    element.innerHTML = '';
    element.appendChild(spinner);
}

export function hideLoading(element) {
    if (!element || element.dataset.loading !== 'true') return;

    element.innerHTML = element.dataset.originalContent;
    element.disabled = false;
    delete element.dataset.loading;
    delete element.dataset.originalContent;
}

export function createSkeleton(type = 'text', count = 1) {
    const skeletons = [];
    for (let i = 0; i < count; i++) {
        const el = document.createElement('div');
        el.className = `skeleton skeleton-${type}`;
        skeletons.push(el);
    }
    return count === 1 ? skeletons[0] : skeletons;
}

// ═══════════════════════════════════════════════════════════
// CONFIRM DIALOG
// ═══════════════════════════════════════════════════════════

export function confirm(message, options = {}) {
    return new Promise((resolve) => {
        const {
            title = 'Confirm',
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            type = 'warning'
        } = options;

        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay active';
        overlay.id = 'confirmOverlay';

        const dialog = document.createElement('div');
        dialog.className = 'modal-content active';
        dialog.style.maxWidth = '400px';
        dialog.innerHTML = `
            <div class="p-xl text-center">
                <h3 class="text-xl font-bold mb-md">${title}</h3>
                <p class="text-gray mb-xl">${message}</p>
                <div class="flex gap-md justify-center">
                    <button class="btn btn-outline" id="confirmCancel">${cancelText}</button>
                    <button class="btn btn-${type === 'danger' ? 'primary' : 'gold'}" id="confirmOk">${confirmText}</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
        document.body.appendChild(dialog);
        document.body.style.overflow = 'hidden';

        const cleanup = (result) => {
            overlay.remove();
            dialog.remove();
            document.body.style.overflow = '';
            resolve(result);
        };

        dialog.querySelector('#confirmCancel').addEventListener('click', () => cleanup(false));
        dialog.querySelector('#confirmOk').addEventListener('click', () => cleanup(true));
        overlay.addEventListener('click', () => cleanup(false));
    });
}

// ═══════════════════════════════════════════════════════════
// QUANTITY SELECTOR
// ═══════════════════════════════════════════════════════════

export function createQuantitySelector(value = 1, min = 1, max = 99, onChange = null) {
    const container = document.createElement('div');
    container.className = 'qty-selector';
    container.innerHTML = `
        <button type="button" class="qty-btn qty-minus" ${value <= min ? 'disabled' : ''}>−</button>
        <input type="number" class="qty-input" value="${value}" min="${min}" max="${max}" readonly>
        <button type="button" class="qty-btn qty-plus" ${value >= max ? 'disabled' : ''}>+</button>
    `;

    const input = container.querySelector('.qty-input');
    const minusBtn = container.querySelector('.qty-minus');
    const plusBtn = container.querySelector('.qty-plus');

    const updateButtons = () => {
        const val = parseInt(input.value);
        minusBtn.disabled = val <= min;
        plusBtn.disabled = val >= max;
    };

    const setValue = (newVal) => {
        const val = Math.min(max, Math.max(min, parseInt(newVal) || min));
        input.value = val;
        updateButtons();
        if (onChange) onChange(val);
    };

    minusBtn.addEventListener('click', () => setValue(parseInt(input.value) - 1));
    plusBtn.addEventListener('click', () => setValue(parseInt(input.value) + 1));

    container.getValue = () => parseInt(input.value);
    container.setValue = setValue;

    return container;
}

// ═══════════════════════════════════════════════════════════
// LAZY LOADING
// ═══════════════════════════════════════════════════════════

const lazyObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const el = entry.target;

            // Lazy load images
            if (el.dataset.src) {
                el.src = el.dataset.src;
                delete el.dataset.src;
            }

            // Lazy load background images
            if (el.dataset.bg) {
                el.style.backgroundImage = `url(${el.dataset.bg})`;
                delete el.dataset.bg;
            }

            // Trigger custom lazy load callback
            if (el.dataset.lazyCallback) {
                const callback = window[el.dataset.lazyCallback];
                if (typeof callback === 'function') callback(el);
            }

            el.classList.add('lazy-loaded');
            lazyObserver.unobserve(el);
        }
    });
}, { rootMargin: '100px' });

export function lazyLoad(selector = '[data-src], [data-bg], [data-lazy-callback]') {
    document.querySelectorAll(selector).forEach(el => {
        if (!el.classList.contains('lazy-loaded')) {
            lazyObserver.observe(el);
        }
    });
}

// Auto-init lazy loading on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => lazyLoad());
} else {
    lazyLoad();
}

// ═══════════════════════════════════════════════════════════
// FORM UTILITIES
// ═══════════════════════════════════════════════════════════

export function validateForm(form) {
    const errors = [];
    const fields = form.querySelectorAll('[required], [data-validate]');

    fields.forEach(field => {
        const value = field.value.trim();
        const name = field.name || field.id || 'Field';

        // Required check
        if (field.required && !value) {
            errors.push({ field, message: `${name} is required` });
            field.classList.add('input-error');
            return;
        }

        // Email validation
        if (field.type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            errors.push({ field, message: 'Invalid email format' });
            field.classList.add('input-error');
            return;
        }

        // Phone validation
        if (field.dataset.validate === 'phone' && value && !/^[\d\s\-+()]{10,}$/.test(value)) {
            errors.push({ field, message: 'Invalid phone number' });
            field.classList.add('input-error');
            return;
        }

        // Min length
        if (field.minLength && value.length < field.minLength) {
            errors.push({ field, message: `${name} must be at least ${field.minLength} characters` });
            field.classList.add('input-error');
            return;
        }

        field.classList.remove('input-error');
    });

    return errors;
}

export function clearFormErrors(form) {
    form.querySelectorAll('.input-error').forEach(f => f.classList.remove('input-error'));
    form.querySelectorAll('.input-error-msg').forEach(m => m.remove());
}

export function serializeForm(form) {
    const data = {};
    new FormData(form).forEach((value, key) => {
        if (data[key]) {
            if (!Array.isArray(data[key])) data[key] = [data[key]];
            data[key].push(value);
        } else {
            data[key] = value;
        }
    });
    return data;
}

// ═══════════════════════════════════════════════════════════
// FORMAT UTILITIES
// ═══════════════════════════════════════════════════════════

export function formatPrice(cents, currency = 'LKR') {
    const amount = (cents / 100).toFixed(2);
    const symbols = { LKR: 'Rs.', USD: '$', EUR: '€', GBP: '£' };
    return `${symbols[currency] || currency} ${amount}`;
}

export function formatDate(dateStr, options = {}) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        ...options
    });
}

export function formatRelativeTime(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now - date;

    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (days > 30) return formatDate(dateStr);
    if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`;
    if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    if (minutes > 0) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    return 'Just now';
}

// ═══════════════════════════════════════════════════════════
// DEBOUNCE & THROTTLE
// ═══════════════════════════════════════════════════════════

export function debounce(fn, delay = 300) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn(...args), delay);
    };
}

export function throttle(fn, limit = 300) {
    let inThrottle;
    return (...args) => {
        if (!inThrottle) {
            fn(...args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ═══════════════════════════════════════════════════════════
// EXPORT DEFAULT
// ═══════════════════════════════════════════════════════════

export default {
    toast,
    showToast,
    openModal,
    closeModal,
    closeAllModals,
    showLoading,
    hideLoading,
    createSkeleton,
    confirm,
    createQuantitySelector,
    lazyLoad,
    validateForm,
    clearFormErrors,
    serializeForm,
    formatPrice,
    formatDate,
    formatRelativeTime,
    debounce,
    throttle
};
