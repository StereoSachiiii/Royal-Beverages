<?php
/**
 * MyAccount Addresses
 * Saved address management
 */
$pageName = 'account';
$pageTitle = 'Saved Addresses - Royal Beverages';
require_once __DIR__ . "/_layout.php";
?>

<div class="space-y-16">
    <!-- Header -->
    <header class="flex flex-col md:flex-row md:items-end md:justify-between gap-8">
        <div>
            <span class="text-xs uppercase tracking-[0.4em] text-gold font-extrabold mb-4 block italic">Address Book</span>
            <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tight leading-none">Your <br>Addresses</h1>
        </div>
        <button id="addAddressBtn" class="h-16 px-12 bg-black text-white text-[10px] uppercase font-black tracking-widest hover:bg-gold transition-all duration-300 flex items-center justify-center gap-4">
            Add New Address
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        </button>
    </header>

    <!-- Address Grid -->
    <section class="grid grid-cols-1 md:grid-cols-2 gap-8" id="addressGrid">
        <!-- Populated via JS -->
    </section>
</div>

<!-- Add/Edit Address Modal (Refactored) -->
<div id="addressModal" class="modal-overlay">
    <div class="modal-content !max-w-[700px] !p-0">
        <div class="p-12 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <div class="flex flex-col">
                <span class="text-[9px] uppercase font-black tracking-[0.4em] text-gold mb-1">Details</span>
                <h2 class="text-xl font-black uppercase tracking-widest" id="modalTitle">Add Address</h2>
            </div>
            <button id="closeModal" class="p-2 hover:bg-gray-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="addressForm" class="p-12 space-y-10">
            <input type="hidden" id="addressId">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="space-y-3">
                    <label class="text-[10px] uppercase font-black tracking-widest text-gray-400">Recipient Label</label>
                    <input type="text" id="nodeName" required class="w-full h-14 bg-gray-50 border border-gray-100 px-6 text-sm font-bold focus:bg-white focus:border-gold outline-none transition-all" placeholder="HOME / OFFICE">
                </div>
                <div class="space-y-3">
                    <label class="text-[10px] uppercase font-black tracking-widest text-gray-400">Full Name</label>
                    <input type="text" id="fullName" required class="w-full h-14 bg-gray-50 border border-gray-100 px-6 text-sm font-bold focus:bg-white focus:border-gold outline-none transition-all">
                </div>
            </div>

            <div class="space-y-3">
                <label class="text-[10px] uppercase font-black tracking-widest text-gray-400">Street Address</label>
                <input type="text" id="street" required class="w-full h-14 bg-gray-50 border border-gray-100 px-6 text-sm font-bold focus:bg-white focus:border-gold outline-none transition-all" placeholder="House Number & Street">
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-10">
                <div class="space-y-3">
                    <label class="text-[10px] uppercase font-black tracking-widest text-gray-400">City</label>
                    <input type="text" id="city" required class="w-full h-14 bg-gray-50 border border-gray-100 px-6 text-sm font-bold focus:bg-white focus:border-gold outline-none transition-all">
                </div>
                <div class="space-y-3">
                    <label class="text-[10px] uppercase font-black tracking-widest text-gray-400">State / Region</label>
                    <input type="text" id="state" required class="w-full h-14 bg-gray-50 border border-gray-100 px-6 text-sm font-bold focus:bg-white focus:border-gold outline-none transition-all">
                </div>
                <div class="space-y-3 col-span-2 md:col-span-1">
                    <label class="text-[10px] uppercase font-black tracking-widest text-gray-400">Postal Code</label>
                    <input type="text" id="zip" required class="w-full h-14 bg-gray-50 border border-gray-100 px-6 text-sm font-bold focus:bg-white focus:border-gold outline-none transition-all">
                </div>
            </div>

            <div class="flex items-center gap-4 pt-10 border-t border-gray-50">
                <button type="submit" class="h-16 px-12 bg-black text-white text-[10px] uppercase font-black tracking-widest hover:bg-gold transition-all duration-300">Save Address</button>
                <button type="button" id="cancelModal" class="h-16 px-8 text-[10px] uppercase font-black tracking-widest text-gray-400 hover:text-black transition-colors">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script type="module">
import { API } from '<?= BASE_URL ?>assets/js/api-helper.js';
import { toast } from '<?= BASE_URL ?>assets/js/toast.js';

const user_id = <?= $session->getUserId() ?>;
let addresses = [];

async function loadAddresses() {
    try {
        const result = await API.addresses.list(user_id);
        addresses = result?.data || [];
        renderAddresses();
    } catch (error) {
        console.error('Error fetching addresses:', error);
        toast.error('Failed to load address book');
    }
}

function renderAddresses() {
    const container = document.getElementById('addressGrid');
    if (addresses.length === 0) {
        container.innerHTML = `
            <div class="col-span-full py-32 text-center bg-white border border-gray-100 flex flex-col items-center justify-center">
                <p class="text-[11px] uppercase tracking-[0.3em] text-gray-400 font-black mb-8 italic opacity-40">No addresses found</p>
                <button onclick="document.getElementById('addAddressBtn').click()" class="text-[10px] uppercase font-black tracking-widest text-gold border-b border-gold/20 pb-1">Add Your First Address</button>
            </div>`;
        return;
    }

    container.innerHTML = addresses.map((addr, idx) => `
        <div class="bg-white border border-gray-100 p-12 hover:border-gold hover:shadow-2xl transition-all duration-500 relative group">
            <div class="flex justify-between items-start mb-10">
                <span class="text-[9px] uppercase font-black tracking-[0.4em] text-gold italic">${addr.nodeName || 'DEFAULT ADDRESS'}</span>
                <div class="flex items-center gap-4 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button class="edit-btn p-2 text-gray-300 hover:text-black transition-colors" data-index="${idx}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button class="delete-btn p-2 text-gray-300 hover:text-red-600 transition-colors" data-index="${idx}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>

            <div class="space-y-2">
                <h3 class="text-xl font-black uppercase tracking-widest text-black">${addr.recipient_name}</h3>
                <div class="text-[11px] uppercase font-extrabold text-gray-400 tracking-widest space-y-1">
                    <p>${addr.address_line1}</p>
                    <p>${addr.city}, ${addr.state} ${addr.postal_code}</p>
                </div>
            </div>

            <div class="mt-10 pt-10 border-t border-gray-50 flex items-center justify-between">
                <span class="text-[8px] uppercase tracking-[0.4em] text-gray-200 font-bold italic">Verified</span>
                <svg class="w-4 h-4 text-gray-100 group-hover:text-gold transition-colors" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            </div>
        </div>
    `).join('');

    // Attach Listeners
    container.querySelectorAll('.edit-btn').forEach(btn => btn.onclick = () => openModal(btn.dataset.index));
    container.querySelectorAll('.delete-btn').forEach(btn => btn.onclick = async () => {
        if (confirm('Delete this address?')) {
            const addr = addresses[btn.dataset.index];
            try {
                await API.addresses.delete(addr.id);
                toast.success('Address removed');
                await loadAddresses();
            } catch (error) {
                toast.error('Failed to remove address');
            }
        }
    });
}

// Modal Logic
const modal = document.getElementById('addressModal');
const form = document.getElementById('addressForm');

function openModal(index = -1) {
    document.getElementById('modalTitle').textContent = index === -1 ? 'Add Address' : 'Edit Address';
    document.getElementById('addressId').value = index;
    
    if (index !== -1) {
        const addr = addresses[index];
        document.getElementById('nodeName').value = addr.address_type || '';
        document.getElementById('fullName').value = addr.recipient_name;
        document.getElementById('street').value = addr.address_line1;
        document.getElementById('city').value = addr.city;
        document.getElementById('state').value = addr.state;
        document.getElementById('zip').value = addr.postal_code;
    } else {
        form.reset();
    }
    
    modal.classList.add('active');
}

document.getElementById('addAddressBtn').onclick = () => openModal();
document.getElementById('closeModal').onclick = document.getElementById('cancelModal').onclick = () => modal.classList.remove('active');

form.onsubmit = async (e) => {
    e.preventDefault();
    const index = parseInt(document.getElementById('addressId').value);
    const data = {
        user_id: user_id,
        address_type: document.getElementById('nodeName').value,
        recipient_name: document.getElementById('fullName').value,
        address_line1: document.getElementById('street').value,
        city: document.getElementById('city').value,
        state: document.getElementById('state').value,
        postal_code: document.getElementById('zip').value,
        phone: 'Not Provided' // Default for now
    };

    try {
        if (index === -1) {
            await API.addresses.create(data);
            toast.success('Address added');
        } else {
            const addr = addresses[index];
            await API.addresses.update(addr.id, data);
            toast.success('Address updated');
        }

        await loadAddresses();
        modal.classList.remove('active');
    } catch (error) {
        console.error('Save error:', error);
        toast.error('Failed to save address');
    }
};

document.addEventListener('DOMContentLoaded', loadAddresses);
</script>

<?php require_once __DIR__ . "/_layout_end.php"; ?>
