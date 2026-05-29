<?php
/**
 * MyAccount - Activity Log
 * User activity history
 */
$pageName = 'activity';
$pageTitle = 'My Activity - Royal Beverages';
require_once __DIR__ . "/_layout.php";
?>

<h1 class="account-page-title">Activity Log</h1>

<div class="activity-filters">
    <button class="filter-tab active" data-filter="all">All Activity</button>
    <button class="filter-tab" data-filter="orders">Orders</button>
    <button class="filter-tab" data-filter="account">Account</button>
    <button class="filter-tab" data-filter="wishlist">Wishlist</button>
</div>

<div class="activity-timeline" id="activityTimeline">
    <!-- Activity items will be rendered here -->
</div>

<div class="empty-state hidden" id="emptyActivity">
    <div class="empty-state-icon">📋</div>
    <h3 class="empty-state-title">No Activity Yet</h3>
    <p class="empty-state-text">Your recent activity will appear here.</p>
    <a href="<?= getPageUrl('shop') ?>" class="btn btn-gold">Start Shopping</a>
</div>

<style>
.activity-filters {
    display: flex;
    gap: var(--space-sm);
    margin-bottom: var(--space-xl);
    flex-wrap: wrap;
}

.filter-tab {
    padding: var(--space-sm) var(--space-lg);
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-full);
    font-size: 0.9rem;
    cursor: pointer;
    transition: all var(--duration-fast);
}

.filter-tab:hover {
    border-color: var(--gray-300);
}

.filter-tab.active {
    background: var(--black);
    color: var(--white);
    border-color: var(--black);
}

.activity-timeline {
    display: flex;
    flex-direction: column;
    gap: var(--space-lg);
}

.activity-item {
    display: flex;
    gap: var(--space-lg);
    padding: var(--space-lg);
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    position: relative;
}

.activity-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-full);
    background: var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.activity-icon.order { background: rgba(212, 175, 55, 0.15); }
.activity-icon.account { background: rgba(59, 130, 246, 0.15); }
.activity-icon.wishlist { background: rgba(239, 68, 68, 0.15); }

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 600;
    color: var(--black);
    margin-bottom: var(--space-xs);
}

.activity-description {
    font-size: 0.9rem;
    color: var(--gray-600);
    margin-bottom: var(--space-sm);
}

.activity-time {
    font-size: 0.8rem;
    color: var(--gray-400);
}

@media (max-width: 640px) {
    .activity-item {
        flex-direction: column;
    }
}
</style>

<script type="module">
import { toast } from '<?= BASE_URL ?>assets/js/toast.js';
import { getOrders } from '<?= BASE_URL ?>assets/js/orders.js';
import { getWishlist } from '<?= BASE_URL ?>assets/js/wishlist-storage.js';

let allActivities = [];
let currentFilter = 'all';

// Generate activity from various sources
const generateActivities = () => {
    allActivities = [];
    
    // Get orders and create activity items
    const orders = getOrders();
    orders.forEach(order => {
        allActivities.push({
            type: 'orders',
            icon: '📦',
            title: `Order #${order.id} placed`,
            description: `${order.items?.length || 0} items, $${((order.total || 0) / 100).toFixed(2)}`,
            time: order.createdAt,
            iconClass: 'order'
        });
    });
    
    // Get wishlist activity (from localStorage)
    const wishlist = getWishlist();
    wishlist.forEach(item => {
        if (item.addedAt) {
            allActivities.push({
                type: 'wishlist',
                icon: '❤️',
                title: 'Added to wishlist',
                description: item.name || 'Product',
                time: item.addedAt,
                iconClass: 'wishlist'
            });
        }
    });
    
    // Get profile updates (from localStorage)
    const profile = JSON.parse(localStorage.getItem('userProfile') || '{}');
    if (profile.updatedAt) {
        allActivities.push({
            type: 'account',
            icon: '👤',
            title: 'Profile updated',
            description: 'Updated profile information',
            time: profile.updatedAt,
            iconClass: 'account'
        });
    }
    
    // Get password changes
    const passwordChanged = localStorage.getItem('passwordLastChanged');
    if (passwordChanged) {
        allActivities.push({
            type: 'account',
            icon: '🔒',
            title: 'Password changed',
            description: 'Security update',
            time: passwordChanged,
            iconClass: 'account'
        });
    }
    
    // Get address updates
    const addresses = JSON.parse(localStorage.getItem('userAddresses') || '[]');
    addresses.forEach(addr => {
        if (addr.updatedAt) {
            allActivities.push({
                type: 'account',
                icon: '📍',
                title: 'Address saved',
                description: `${addr.city}, ${addr.state}`,
                time: addr.updatedAt,
                iconClass: 'account'
            });
        }
    });
    
    // Get contact messages sent
    const messages = JSON.parse(localStorage.getItem('contactMessages') || '[]');
    messages.forEach(msg => {
        allActivities.push({
            type: 'account',
            icon: '✉️',
            title: 'Message sent',
            description: `Subject: ${msg.subject}`,
            time: msg.createdAt,
            iconClass: 'account'
        });
    });
    
    // Sort by time descending
    allActivities.sort((a, b) => new Date(b.time) - new Date(a.time));
};

// Render activities
const renderActivities = () => {
    const timeline = document.getElementById('activityTimeline');
    const emptyState = document.getElementById('emptyActivity');
    
    let filtered = currentFilter === 'all' 
        ? allActivities 
        : allActivities.filter(a => a.type === currentFilter);
    
    timeline.innerHTML = '';
    
    if (filtered.length === 0) {
        emptyState.classList.remove('hidden');
        return;
    }
    
    emptyState.classList.add('hidden');
    
    filtered.forEach(activity => {
        const timeAgo = formatTimeAgo(new Date(activity.time));
        
        const item = document.createElement('div');
        item.className = 'activity-item';
        item.innerHTML = `
            <div class="activity-icon ${activity.iconClass}">${activity.icon}</div>
            <div class="activity-content">
                <div class="activity-title">${activity.title}</div>
                <div class="activity-description">${activity.description}</div>
                <div class="activity-time">${timeAgo}</div>
            </div>
        `;
        timeline.appendChild(item);
    });
};

// Format time ago
const formatTimeAgo = (date) => {
    const now = new Date();
    const diff = now - date;
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    
    if (days > 7) {
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    } else if (days > 0) {
        return `${days} day${days > 1 ? 's' : ''} ago`;
    } else if (hours > 0) {
        return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    } else if (minutes > 0) {
        return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    } else {
        return 'Just now';
    }
};

// Filter tabs
document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        currentFilter = tab.dataset.filter;
        renderActivities();
    });
});

// Initialize
generateActivities();
renderActivities();
console.log('[Activity] Activity page ready');
</script>

<?php require_once __DIR__ . "/_layout_end.php"; ?>
