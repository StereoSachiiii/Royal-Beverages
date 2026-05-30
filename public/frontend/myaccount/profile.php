
<?php
require_once __DIR__ . '/../components/header.php';
?>



<?php if(!$session->isLoggedIn()): ?> 
<div class="space-y-16">
    <header>
        <span class="text-xs uppercase tracking-[0.4em] text-gray-400 font-extrabold mb-4 block italic">Identification</span>
        <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tight leading-none">Authentication <br>Required</h1>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
        
        <div class="bg-white border border-gray-100 p-12 hover:border-black transition-colors">
            <h2 class="text-xs uppercase font-black tracking-[0.3em] mb-4">Existing Client</h2>
            <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-12">Please provide credentials</p>
            
            <form action="<?= BASE_URL ?>auth.php?action=login" method="POST" class="space-y-6">
                <div>
                    <label class="block text-[9px] uppercase font-black tracking-[0.2em] mb-2">Email Address</label>
                    <input type="email" name="email" required class="w-full border border-gray-200 p-4 text-[11px] uppercase tracking-widest focus:border-black focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-[9px] uppercase font-black tracking-[0.2em] mb-2">Private Key (Password)</label>
                    <input type="password" name="password" required class="w-full border border-gray-200 p-4 text-[11px] font-mono tracking-widest focus:border-black focus:outline-none transition-colors">
                </div>
                
                <button type="submit" class="w-full bg-black text-white p-4 text-[10px] uppercase font-black tracking-[0.3em] mt-8 hover:bg-gold transition-colors">Authenticate</button>
            </form>
        </div>
        
        <div class="bg-white border border-gray-100 p-12 hover:border-black transition-colors flex flex-col">
            <h2 class="text-xs uppercase font-black tracking-[0.3em] mb-4">New Request</h2>
            <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-12">Establish a portfolio</p>
            
            <ul class="text-[10px] uppercase font-bold text-gray-400 tracking-widest space-y-4 mb-12 flex-grow">
                <li class="flex items-center gap-4"><div class="w-1.5 h-1.5 bg-black"></div> Swift Checkout Integration</li>
                <li class="flex items-center gap-4"><div class="w-1.5 h-1.5 bg-black"></div> AI Palate Recommendation Engine</li>
                <li class="flex items-center gap-4"><div class="w-1.5 h-1.5 bg-black"></div> Multi-device Wishlist Sync</li>
            </ul>
            
            <a href="<?= BASE_URL ?>auth.php?action=register" class="w-full text-center border border-black p-4 text-[10px] uppercase font-black tracking-[0.3em] hover:bg-black hover:text-white transition-colors">Register Identity</a>
        </div>
        
    </div>
</div>
<?php else: ?>
    <div class="space-y-16 my-account-dashboard">
        
        <header>
            <span class="text-xs uppercase tracking-[0.4em] text-gold font-extrabold mb-4 block italic">Portfolio</span>
            <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tight leading-none">Identity <br>Matrix</h1>
            <p class="text-[10px] uppercase tracking-widest font-bold text-gray-400 mt-6">Active Session: <?= htmlspecialchars($session->getUsername()) ?></p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            
            <div class="bg-white border border-gray-100 p-12 group hover:border-black transition-all">
                <h2 class="text-xs uppercase font-black tracking-[0.3em] mb-12 border-b border-gray-100 pb-4">Personal Parameters</h2>
                <div class="space-y-6">
                    <div>
                        <span class="text-[9px] uppercase font-bold text-gray-400 tracking-widest block mb-1">Designation</span>
                        <span class="text-[11px] font-black uppercase tracking-widest"><?= htmlspecialchars($session->getUsername()) ?></span>
                    </div>
                    <div>
                        <span class="text-[9px] uppercase font-bold text-gray-400 tracking-widest block mb-1">Comms Relay</span>
                        <span class="text-[11px] font-black uppercase tracking-widest lowercase"><?= htmlspecialchars($session->getEmail()) ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-100 p-12 group hover:border-black transition-all">
                <div class="flex items-center justify-between mb-12 border-b border-gray-100 pb-4">
                    <h2 class="text-xs uppercase font-black tracking-[0.3em] flex items-center gap-2">Known Logistics </h2>
                    <a href="<?= BASE_URL?>myaccount/addresses.php" class="text-[9px] uppercase tracking-widest font-black hover:text-gold transition-colors text-gray-400">Manage</a>
                </div>
                <div class="addresses space-y-4">
                    <!-- JS Injected Addresses -->
                    <div class="text-[10px] uppercase font-bold text-gray-400 tracking-widest animate-pulse">Syncing logistics endpoint...</div>
                </div>
            </div>
            
            <div class="bg-black text-white p-12 flex flex-col md:col-span-2 relative overflow-hidden">
                <div class="absolute -right-20 -top-20 opacity-10 pointer-events-none">
                    <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="M5 3l3.057-3 11.943 12-11.943 12-3.057-3 9-9z"/></svg>
                </div>
                <h2 class="text-xs uppercase font-black tracking-[0.3em] mb-4 text-gold z-10 block">Protocol Termination</h2>
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest max-w-sm leading-relaxed mb-8 z-10">Flush session parameters and sever connection with the mainframe.</p>
                
                <a href="<?= BASE_URL ?>myaccount/logout.php" class="w-max border border-white text-white p-4 px-12 text-[10px] uppercase font-black tracking-[0.3em] hover:bg-white hover:text-black transition-colors z-10">Sever Connection</a>
            </div>

        </div>
    </div>
<?php endif; ?>

    <script type="module">
        import {getAddresses} from '../assets/js/addresses.js'
        const dashboardGrid = document.querySelector('.my-account-dashboard')
        const addresses = document.querySelector('.addresses')


       

        const parseAdresses = async (addressList) => {
            if (!addressList || addressList.length === 0) {
                return '<span class="text-[9px] uppercase font-bold text-gray-400 tracking-widest">No recognized coordinates.</span>';
            }
            let html = addressList.map(((address) =>(`
            <div class="border border-gray-100 p-6 flex flex-col gap-2 hover:border-black transition-colors">
                <span class="text-[8px] uppercase tracking-[0.4em] font-black underline underline-offset-4 mb-2">${address.type || 'Primary'}</span>
                <span class="text-[10px] font-bold tracking-widest uppercase text-black leading-relaxed">
                    ${address.address_line1 || '-'}<br>
                    ${address.address_line2 ? address.address_line2 + '<br>' : ''}
                    ${address.city || '-'}, ${address.state || '-'}<br>
                    ${address.postal_code || '-'}
                </span>
            </div>
            `))).join('');
            return html;
        }   

        dashboardGrid.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        })

        document.addEventListener('DOMContentLoaded',async ()=>{
            
            const id = Number.parseInt(<?= $session->getUserId()?>)
           
            const addressList = await getAddresses(id)
           
            const html = await parseAdresses(addressList)
            addresses.innerHTML = html
            
        })
       
    </script>
