/**
 * Toast Notification Utility
 * Provides a simple, elegant toast notification system
 */

class Toast {
  constructor() {
    this.container = null;
    this.init();
  }

  init() {
    // Create container if it doesn't exist
    if (!document.getElementById('toast-container')) {
      this.container = document.createElement('div');
      this.container.id = 'toast-container';
      this.container.className = 'toast-container';
      document.body.appendChild(this.container);

      // Add styles
      this.addStyles();
    } else {
      this.container = document.getElementById('toast-container');
    }
  }

  addStyles() {
    if (document.getElementById('toast-styles')) return;

    const style = document.createElement('style');
    style.id = 'toast-styles';
    style.textContent = `
            .toast-container {
                position: fixed;
                bottom: 24px;
                right: 24px;
                z-index: 10000;
                display: flex;
                flex-direction: column;
                gap: 12px;
                pointer-events: none;
            }
            
            .toast {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 16px 20px;
                background: #1a1a1a;
                color: #fff;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                font-size: 0.95rem;
                font-weight: 500;
                pointer-events: auto;
                animation: toastSlideIn 0.3s ease;
                max-width: 380px;
            }
            
            .toast.toast-exiting {
                animation: toastSlideOut 0.3s ease forwards;
            }
            
            .toast-icon {
                font-size: 1.25rem;
                flex-shrink: 0;
            }
            
            .toast-message {
                flex: 1;
            }
            
            .toast-close {
                background: none;
                border: none;
                color: rgba(255,255,255,0.6);
                cursor: pointer;
                padding: 4px;
                font-size: 1.25rem;
                line-height: 1;
                transition: color 0.2s;
            }
            
            .toast-close:hover {
                color: #fff;
            }
            
            .toast.success {
                background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            }
            
            .toast.error {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            }
            
            .toast.warning {
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                color: #000;
            }
            
            .toast.warning .toast-close {
                color: rgba(0,0,0,0.6);
            }
            
            .toast.warning .toast-close:hover {
                color: #000;
            }
            
            .toast.info {
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            }
            
            .toast.gold {
                background: linear-gradient(135deg, #d4af37 0%, #f4cf47 100%);
                color: #000;
            }
            
            .toast.gold .toast-close {
                color: rgba(0,0,0,0.6);
            }
            
            .toast.gold .toast-close:hover {
                color: #000;
            }
            
            @keyframes toastSlideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes toastSlideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
            
            @media (max-width: 480px) {
                .toast-container {
                    left: 16px;
                    right: 16px;
                    bottom: 16px;
                }
                
                .toast {
                    max-width: 100%;
                }
            }
        `;
    document.head.appendChild(style);
  }

  show(message, type = 'info', duration = 4000) {
    this.init(); // Ensure container exists

    const icons = {
      success: '✓',
      error: '✕',
      warning: '⚠',
      info: 'ℹ',
      gold: '★',
    };

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
            <span class="toast-icon">${icons[type] || icons.info}</span>
            <span class="toast-message">${message}</span>
            <button class="toast-close" aria-label="Close">×</button>
        `;

    // Close button handler
    toast.querySelector('.toast-close').addEventListener('click', () => {
      this.dismiss(toast);
    });

    this.container.appendChild(toast);

    // Auto dismiss
    if (duration > 0) {
      setTimeout(() => {
        this.dismiss(toast);
      }, duration);
    }

    return toast;
  }

  dismiss(toast) {
    if (!toast || toast.classList.contains('toast-exiting')) return;

    toast.classList.add('toast-exiting');
    setTimeout(() => {
      toast.remove();
    }, 300);
  }

  success(message, duration = 4000) {
    return this.show(message, 'success', duration);
  }

  error(message, duration = 5000) {
    return this.show(message, 'error', duration);
  }

  warning(message, duration = 4500) {
    return this.show(message, 'warning', duration);
  }

  info(message, duration = 4000) {
    return this.show(message, 'info', duration);
  }

  gold(message, duration = 4000) {
    return this.show(message, 'gold', duration);
  }
}

// Create singleton instance
const toast = new Toast();

// Export for ES modules
export { toast, Toast };
export default toast;
