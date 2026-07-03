<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

if (!has_permission('use_pos')) {
    die('Access Denied. You do not have permission to use the POS terminal.');
}

// Fetch categories for the filter buttons
$categories_stmt = $pdo->query("SELECT name_en FROM categories ORDER BY name_en ASC");
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);
// Fetch tax rate
$tax_rate = get_setting($pdo, 'tax_percentage', 5) / 100;
$tailwind_css = load_tailwind_css([
    __DIR__ . '/assets/css/tailwind.css',
    dirname(__DIR__) . '/assets/css/tailwind.css',
]);
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PAICAFE POS | Terminal</title>
    <script>
        (function () {
            const storedTheme = localStorage.getItem('paicafe-theme') || localStorage.getItem('darkMode');
            const useDark = storedTheme === 'dark' || storedTheme === 'true' || (!storedTheme && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', useDark);
            document.documentElement.classList.toggle('light', !useDark);
            document.documentElement.dataset.theme = useDark ? 'dark' : 'light';
        })();
    </script>
    <style><?= $tailwind_css ?></style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200;400;600;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            color-scheme: light;
            --pos-bg: #edf7f4;
            --pos-text: #14323a;
            --pos-muted: #64748b;
            --pos-faint: #8aa0a8;
            --pos-border: rgba(21, 94, 117, 0.16);
            --pos-panel: linear-gradient(145deg, rgba(255, 255, 255, 0.86), rgba(232, 247, 243, 0.7));
            --pos-card: linear-gradient(180deg, rgba(255, 255, 255, 0.88), rgba(242, 251, 248, 0.72));
            --pos-soft: rgba(255, 255, 255, 0.62);
            --pos-rail: rgba(230, 248, 244, 0.76);
            --pos-field: rgba(255, 255, 255, 0.78);
            --pos-shadow: 0 24px 70px rgba(15, 76, 91, 0.14), inset 0 1px 0 rgba(255, 255, 255, 0.7);
        }

        .dark {
            color-scheme: dark;
            --pos-bg: #061113;
            --pos-text: #f8fafc;
            --pos-muted: #94a3b8;
            --pos-faint: #64748b;
            --pos-border: rgba(184, 222, 226, 0.18);
            --pos-panel: linear-gradient(145deg, rgba(20, 48, 58, 0.72), rgba(13, 31, 38, 0.58));
            --pos-card: linear-gradient(180deg, rgba(255, 255, 255, 0.075), rgba(255, 255, 255, 0.03));
            --pos-soft: rgba(255, 255, 255, 0.055);
            --pos-rail: rgba(10, 26, 32, 0.8);
            --pos-field: rgba(255, 255, 255, 0.055);
            --pos-shadow: 0 28px 80px rgba(0, 0, 0, 0.42), inset 0 1px 0 rgba(255, 255, 255, 0.12);
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif;
            background:
                radial-gradient(circle at 18% 10%, rgba(94, 234, 212, 0.18), transparent 26%),
                radial-gradient(circle at 82% 16%, rgba(167, 139, 250, 0.16), transparent 28%),
                radial-gradient(circle at 78% 92%, rgba(251, 191, 36, 0.12), transparent 28%),
                var(--pos-bg);
            color: var(--pos-text);
            overflow: hidden;
        }

        .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 10px; }

        .glass-panel {
            background: var(--pos-panel);
            backdrop-filter: blur(24px) saturate(1.42);
            -webkit-backdrop-filter: blur(24px) saturate(1.42);
            border: 1px solid var(--pos-border);
            box-shadow: var(--pos-shadow);
        }

        .product-card {
            background: var(--pos-card);
            border: 1px solid var(--pos-border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .product-card:hover {
            background: rgba(94, 234, 212, 0.1);
            border-color: rgba(94, 234, 212, 0.4);
            transform: translateY(-2px);
        }

        .soft-panel {
            background: var(--pos-soft);
            border: 1px solid var(--pos-border);
            backdrop-filter: blur(18px) saturate(1.24);
            -webkit-backdrop-filter: blur(18px) saturate(1.24);
        }

        .pos-rail { background: var(--pos-rail); }
        .pos-field { background: var(--pos-field) !important; border-color: var(--pos-border) !important; }
        .pos-muted { color: var(--pos-muted) !important; }
        .pos-faint { color: var(--pos-faint) !important; }
        .pos-shell { min-height: 100svh; height: 100svh; }
        .pos-checkout { width: min(420px, 100%); }

        .touch-button {
            min-height: 44px;
        }

        .active-category {
            background: linear-gradient(135deg, #0f766e, #7c3aed) !important;
            color: white !important;
            box-shadow: 0 4px 16px rgba(20, 184, 166, 0.28);
        }

        .active-mode {
            background: rgba(94, 234, 212, 0.16) !important;
            color: #99f6e4 !important;
            border-color: rgba(94, 234, 212, 0.36) !important;
        }

        #rotate-overlay { 
            display: none; 
            position: fixed; 
            inset: 0; 
            background: #061113; 
            z-index: 1000; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
        }

        .orientation-hint { display: none; }

        .light .pos-page .text-white { color: var(--pos-text) !important; }
        .light .pos-page .text-slate-200,
        .light .pos-page .text-slate-300,
        .light .pos-page .text-slate-400 { color: var(--pos-muted) !important; }
        .light .pos-page .bg-slate-800 { background-color: rgba(15, 76, 91, 0.1) !important; }
        .light .pos-page .bg-black\/20 { background-color: rgba(15, 76, 91, 0.08) !important; }
        .light .pos-page .active-category,
        .light .pos-page .bg-orange-600,
        .light .pos-page .bg-red-600,
        .light .pos-page .hover\:bg-red-500:hover,
        .light .pos-page .hover\:bg-teal-500:hover,
        .light .pos-page .group:hover .group-hover\:text-white,
        .light .pos-page .voucher-print-area .text-white { color: #ffffff !important; }
        .light .pos-page .voucher-print-area,
        .light .pos-page .voucher-print-area .text-slate-900 { color: #0f172a !important; }

        @media (max-width: 1279px) {
            body { overflow: auto; }
            .pos-shell { height: auto; min-height: 100svh; overflow: visible; }
            .pos-layout { flex-direction: column; overflow: visible; }
            .pos-products { border-right: 0; min-height: 62svh; }
            .pos-checkout { width: 100%; min-height: 520px; }
            .pos-product-scroll { min-height: 420px; }
        }

        @media (max-width: 768px) {
            .orientation-hint { display: flex; }
            .pos-shell { padding: 0.625rem; }
            .pos-header { gap: 0.75rem; align-items: stretch; }
            .pos-header-actions { width: 100%; flex-wrap: wrap; gap: 0.5rem; }
            .pos-search { flex: 1 1 100%; width: 100%; }
            .pos-search input { width: 100%; }
            .pos-product-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; }
            .pos-section-pad { padding-left: 1rem; padding-right: 1rem; }
            .pos-checkout { border-radius: 1.35rem; }
            .pos-payment-panel { left: 0; max-width: none; }
            .pos-payment-panel .p-8 { padding: 1rem; }
            .pos-payment-panel .text-6xl { font-size: 2.35rem; line-height: 1; }
            .pos-payment-panel .rounded-\[2\.5rem\] { border-radius: 1.25rem; }
        }

        @media (max-width: 420px) {
            .pos-product-grid { grid-template-columns: repeat(1, minmax(0, 1fr)); }
            .pos-total { font-size: 1.85rem; }
        }

        @media print {
            body { background: white; color: black; }
            .no-print { display: none !important; }
            .print-only { display: block !important; }
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full pos-page" x-data="posSystem(<?= htmlspecialchars(json_encode($categories)) ?>, <?= $tax_rate ?>)">
    
    <!-- ROTATE NOTIFICATION -->
    <div id="rotate-overlay" class="text-center p-10">
        <div class="w-24 h-24 bg-orange-600/10 rounded-3xl flex items-center justify-center mb-6 animate-bounce">
            <i class="fas fa-rotate text-orange-500 text-4xl"></i>
        </div>
        <h2 class="text-2xl font-black tracking-tight text-white">ROTATE DEVICE</h2>
        <p class="text-slate-400 mt-2">The POS terminal requires landscape orientation for optimal operation.</p>
    </div>

    <!-- MAIN POS INTERFACE -->
    <div class="pos-shell flex h-full overflow-hidden p-3 sm:p-4 lg:p-6">
    <div class="pos-layout flex h-full overflow-hidden w-full gap-4">
        
        <!-- LEFT: PRODUCT GRID -->
        <div class="pos-products flex-1 flex flex-col min-w-0 border-r border-white/5 glass-panel overflow-hidden">
            <!-- Header -->
            <header class="pos-header px-4 sm:px-6 py-4 flex items-center justify-between flex-wrap no-print border-b border-white/5">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-orange-600 rounded-xl flex items-center justify-center shadow-lg shadow-orange-600/20">
                        <i class="fas fa-terminal text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-black tracking-tighter text-white">Counter POS</h1>
                        <p class="text-[10px] text-orange-500 font-bold uppercase tracking-widest">Ready for orders</p>
                    </div>
                </div>
                
                <div class="pos-header-actions flex items-center gap-2 sm:gap-4">
                    <div class="orientation-hint items-center gap-2 rounded-xl bg-orange-500/10 border border-orange-500/20 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-orange-500">
                        <i class="fas fa-mobile-screen"></i>
                        <span>Mobile fit</span>
                    </div>
                    <div class="pos-search relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                        <input type="text" x-model.debounce.300ms="searchTerm" placeholder="Search product..." 
                               class="pos-field border rounded-xl pl-9 pr-4 py-2 text-sm text-white focus:outline-none focus:border-orange-500/50 w-64 transition-all">
                    </div>
                    <button @click="showHeldOrders = true" class="relative h-10 px-4 flex items-center gap-2 rounded-xl soft-panel text-slate-300 hover:text-white hover:bg-white/10 transition-all text-xs font-black uppercase tracking-widest">
                        <i class="fas fa-inbox"></i>
                        <span>Held</span>
                        <span x-show="heldOrders.length" x-text="heldOrders.length" class="absolute -top-2 -right-2 min-w-5 h-5 px-1 rounded-full bg-orange-600 text-white text-[10px] flex items-center justify-center"></span>
                    </button>
                    <button @click="toggleTheme()" class="w-10 h-10 flex items-center justify-center rounded-xl soft-panel text-slate-400 hover:text-white hover:bg-white/10 transition-all" aria-label="Toggle color theme" title="Toggle theme">
                        <i class="fas" :class="darkMode ? 'fa-sun text-yellow-400' : 'fa-moon text-blue-500'"></i>
                    </button>
                    <a href="index.php" class="w-10 h-10 flex items-center justify-center rounded-xl soft-panel text-slate-400 hover:text-white hover:bg-white/10 transition-all" aria-label="Back office" title="Back office">
                        <i class="fas fa-house-user"></i>
                    </a>
                </div>
            </header>

            <!-- Categories -->
            <div class="pos-rail border-b border-white/5 px-4 sm:px-6 py-3 no-print">
                <div class="flex items-center space-x-2 overflow-x-auto custom-scrollbar pb-1">
                    <button @click="selectedCategory = 'All'" 
                            :class="selectedCategory === 'All' ? 'active-category' : 'bg-white/5 text-slate-400 hover:bg-white/10'"
                            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap">
                        All Items
                    </button>
                    <template x-for="category in categories">
                        <button @click="selectedCategory = category" 
                                :class="selectedCategory === category ? 'active-category' : 'bg-white/5 text-slate-400 hover:bg-white/10'"
                                x-text="category"
                                class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap">
                        </button>
                    </template>
                </div>
            </div>

            <!-- Favorites / Quick Picks -->
            <div x-show="favoriteProducts.length" class="pos-rail border-b border-white/5 px-4 sm:px-6 py-3 no-print">
                <div class="flex items-center gap-3 overflow-x-auto custom-scrollbar pb-1">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-teal-300 flex-shrink-0">Quick Picks</span>
                    <template x-for="product in favoriteProducts" :key="'fav-' + product.id">
                        <button @click="addToCart(product)"
                                class="touch-button flex items-center gap-3 rounded-2xl soft-panel px-3 py-2 hover:border-orange-500/40 hover:bg-orange-500/10 transition-all flex-shrink-0">
                            <img :src="product.image || '/assets/uploads/placeholder.png'" class="w-9 h-9 rounded-xl object-cover" alt="">
                            <span class="text-xs font-bold text-slate-100 max-w-32 truncate" x-text="product.name_en"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Products Grid -->
            <main class="pos-product-scroll flex-1 overflow-y-auto p-4 sm:p-6 custom-scrollbar">
                <div x-show="loading" class="flex flex-col items-center justify-center h-full text-slate-500">
                    <i class="fas fa-circle-notch fa-spin text-3xl mb-4 text-orange-600"></i>
                    <p class="font-mono text-xs uppercase tracking-widest">Loading menu items...</p>
                </div>

                <div x-show="!loading && filteredProducts.length === 0" class="flex flex-col items-center justify-center h-full text-slate-600">
                    <i class="fas fa-box-open text-5xl mb-4 opacity-20"></i>
                    <p class="font-mono text-xs uppercase tracking-widest">No matching products found.</p>
                </div>

                <div x-show="!loading" class="pos-product-grid grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5 gap-4">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <div @click="addToCart(product)" 
                             class="product-card group rounded-2xl p-3 cursor-pointer flex flex-col relative overflow-hidden">
                            
                            <!-- Discount Badge -->
                            <template x-if="product.discount_percentage > 0">
                                <div class="absolute top-0 right-0 bg-red-600 text-white text-[9px] font-black px-2 py-1 rounded-bl-xl z-10 shadow-lg">
                                    -<span x-text="parseFloat(product.discount_percentage)"></span>%
                                </div>
                            </template>

                            <div class="aspect-square rounded-xl overflow-hidden mb-3 bg-slate-800">
                                <img :src="product.image || '/assets/uploads/placeholder.png'" 
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            </div>
                            
                            <h3 class="text-xs font-bold text-slate-200 line-clamp-2 mb-2 flex-grow" x-text="product.name_en"></h3>
                            
                            <div class="flex flex-col mt-auto">
                                <template x-if="product.discount_percentage > 0">
                                    <span class="text-[10px] text-slate-500 line-through leading-none" x-text="formatCurrency(product.price)"></span>
                                </template>
                                <div class="flex items-center justify-between">
                                    <p class="text-orange-500 font-black text-sm" x-text="formatCurrency(product.price - (product.price * product.discount_percentage / 100))"></p>
                                    <div class="w-6 h-6 rounded-lg bg-orange-600/10 flex items-center justify-center group-hover:bg-orange-600 transition-colors">
                                        <i class="fas fa-plus text-[10px] text-orange-500 group-hover:text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </main>
        </div>

        <!-- RIGHT: CHECKOUT SIDEBAR -->
        <div class="pos-checkout flex flex-col glass-panel shadow-2xl z-20 overflow-hidden">
            <!-- Header -->
            <div class="p-4 sm:p-6 border-b border-white/5 bg-white/[0.02]">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black text-white tracking-tight uppercase">Current Order</h2>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">
                            <span x-text="cartItemCount"></span> items selected
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="holdCurrentOrder()" :disabled="Object.keys(cart).length === 0" class="w-10 h-10 rounded-xl bg-teal-500/10 text-teal-300 hover:bg-teal-500 hover:text-white disabled:opacity-30 transition-all" title="Hold order">
                            <i class="fas fa-pause text-xs"></i>
                        </button>
                        <button @click="resetOrder()" class="w-10 h-10 rounded-xl bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white transition-all" title="Clear order">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="px-4 sm:px-6 pt-5 no-print">
                <div class="grid grid-cols-2 gap-3">
                    <button @click="orderMode = 'takeaway'" :class="orderMode === 'takeaway' ? 'active-mode' : 'soft-panel text-slate-400'" class="touch-button rounded-2xl text-xs font-black uppercase tracking-widest transition-all">
                        <i class="fas fa-bag-shopping mr-2"></i>Takeaway
                    </button>
                    <button @click="orderMode = 'dine_in'" :class="orderMode === 'dine_in' ? 'active-mode' : 'soft-panel text-slate-400'" class="touch-button rounded-2xl text-xs font-black uppercase tracking-widest transition-all">
                        <i class="fas fa-mug-hot mr-2"></i>Dine In
                    </button>
                </div>
            </div>

            <!-- Cart Items -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-4 sm:p-6 space-y-4">
                <template x-if="Object.keys(cart).length === 0">
                    <div class="h-full flex flex-col items-center justify-center text-slate-600 text-center opacity-40">
                        <i class="fas fa-shopping-basket text-5xl mb-4"></i>
                        <p class="text-xs font-mono uppercase tracking-widest leading-loose">No items yet.<br>Select a product to start.</p>
                    </div>
                </template>

                <template x-for="(item, productId) in cart" :key="productId">
                    <div class="flex items-center bg-white/5 rounded-2xl p-3 border border-white/5 group">
                        <div class="w-12 h-12 rounded-lg bg-slate-800 overflow-hidden flex-shrink-0">
                            <img :src="item.image || '/assets/uploads/placeholder.png'" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-grow px-3 min-w-0">
                            <p class="text-xs font-bold text-white truncate" x-text="item.name"></p>
                            <p class="text-[10px] text-orange-500 font-black mt-0.5" x-text="formatCurrency(item.price)"></p>
                        </div>
                        <div class="flex items-center bg-black/20 rounded-xl p-1">
                            <button @click="updateQuantity(productId, -1)" class="w-6 h-6 flex items-center justify-center rounded-lg hover:bg-white/10 transition-colors">
                                <i class="fas fa-minus text-[8px] text-slate-400"></i>
                            </button>
                            <input type="text" :value="item.quantity" @change="manualUpdateQuantity(productId, $event.target.value)" 
                                   class="w-8 text-center bg-transparent border-none text-xs font-black text-white focus:ring-0">
                            <button @click="updateQuantity(productId, 1)" class="w-6 h-6 flex items-center justify-center rounded-lg hover:bg-white/10 transition-colors">
                                <i class="fas fa-plus text-[8px] text-slate-400"></i>
                            </button>
                        </div>
                        <button @click="removeFromCart(productId)" class="ml-2 w-8 h-8 flex items-center justify-center text-slate-600 hover:text-red-500 transition-colors">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                </template>
            </div>

            <!-- Footer / Totals -->
            <div class="p-4 sm:p-6 pos-rail border-t border-white/5 space-y-4 no-print">
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Order Label</label>
                    <input type="text" x-model="orderLabel" placeholder="Name, table, or short note" 
                           class="w-full pos-field border rounded-2xl px-4 py-3 text-sm text-white focus:border-orange-500/50 focus:outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between text-xs font-bold">
                        <span class="text-slate-500 uppercase tracking-widest">Subtotal</span>
                        <span class="text-slate-200" x-text="formatCurrency(totals.subtotal)"></span>
                    </div>
                    <template x-if="couponDiscount > 0">
                        <div class="flex justify-between text-xs font-bold text-emerald-500">
                            <span class="uppercase tracking-widest">Voucher (<span x-text="couponCode"></span>)</span>
                            <span x-text="'-' + formatCurrency(couponDiscount)"></span>
                        </div>
                    </template>
                    <div class="flex justify-between text-xs font-bold">
                        <span class="text-slate-500 uppercase tracking-widest">Gov Tax (<span x-text="taxRate * 100"></span>%)</span>
                        <span class="text-slate-200" x-text="formatCurrency(totals.tax)"></span>
                    </div>
                </div>
                
                <div class="pt-4 border-t border-white/5 flex justify-between items-end">
                    <div>
                        <p class="text-[10px] font-black text-orange-500 uppercase tracking-[0.2em] mb-1">Payable Total</p>
                        <h3 class="pos-total text-4xl font-black text-white tracking-tighter leading-none" x-text="formatCurrency(totals.final)"></h3>
                    </div>
                    <button @click="openPaymentModal = true" 
                            :disabled="Object.keys(cart).length === 0" 
                            class="bg-orange-600 hover:bg-orange-500 disabled:opacity-30 disabled:hover:bg-orange-600 text-white w-14 h-14 rounded-2xl flex items-center justify-center shadow-lg shadow-orange-600/30 transition-all active:scale-95">
                        <i class="fas fa-chevron-right text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    </div>
    
    <!-- PAYMENT MODAL -->
    <div x-show="openPaymentModal" x-cloak class="fixed inset-0 z-[100] no-print">
        <div class="absolute inset-0 bg-slate-950/90 backdrop-blur-xl" @click="openPaymentModal = false"></div>
        <div class="pos-payment-panel absolute right-0 top-0 bottom-0 w-full max-w-xl glass-panel shadow-2xl border-l border-white/5 flex flex-col">
            
            <div class="p-8 border-b border-white/5 flex items-center justify-between">
                <h2 class="text-2xl font-black text-white tracking-tight uppercase">Checkout</h2>
                <button @click="openPaymentModal = false" class="text-slate-500 hover:text-white transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar p-8 space-y-8">
                <!-- Total Display -->
                <div class="text-center p-8 bg-white/[0.02] rounded-[2.5rem] border border-white/5">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.4em] mb-4">Customer Pays</p>
                    <h3 class="text-6xl font-black text-white tracking-tighter" x-text="formatCurrency(totals.final)"></h3>
                </div>

                <!-- Input Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Left: Customer & Voucher -->
                    <div class="space-y-6">
                        <div>
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 block">Customer Phone</label>
                            <div class="relative">
                                <input type="tel" x-model="customerPhone" placeholder="09..." 
                                       class="w-full pos-field border rounded-2xl px-5 py-4 text-white focus:border-orange-500/50 focus:outline-none transition-all">
                                <button @click="findCustomer()" class="absolute right-3 top-1/2 -translate-y-1/2 text-orange-500 font-black text-[10px] uppercase tracking-widest">Verify</button>
                            </div>
                            <p x-text="customerMessage" class="text-[10px] mt-2 font-bold px-2" :class="customerName ? 'text-emerald-500' : 'text-slate-500'"></p>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 block">Voucher Code</label>
                            <div class="relative">
                                <input type="text" x-model="couponCode" placeholder="Enter code..." 
                                       class="w-full pos-field border rounded-2xl px-5 py-4 text-white focus:border-orange-500/50 focus:outline-none transition-all uppercase">
                                <button @click="applyCoupon()" class="absolute right-3 top-1/2 -translate-y-1/2 text-orange-500 font-black text-[10px] uppercase tracking-widest">Apply</button>
                            </div>
                            <p x-text="couponMessage" class="text-[10px] mt-2 font-bold px-2" :class="couponDiscount > 0 ? 'text-emerald-500' : 'text-red-500'"></p>
                        </div>
                    </div>

                    <!-- Right: Payment Method -->
                    <div class="space-y-6">
                        <div>
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 block">Payment Channel</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button @click="paymentMethod = 'Cash'" 
                                        :class="paymentMethod === 'Cash' ? 'border-orange-600 bg-orange-600/10 text-white' : 'border-white/10 bg-white/5 text-slate-400'"
                                        class="flex flex-col items-center justify-center p-4 rounded-2xl border transition-all">
                                    <i class="fas fa-money-bill-wave mb-2"></i>
                                    <span class="text-[10px] font-black uppercase">Cash</span>
                                </button>
                                <button @click="paymentMethod = 'Online'" 
                                        :class="paymentMethod === 'Online' ? 'border-orange-600 bg-orange-600/10 text-white' : 'border-white/10 bg-white/5 text-slate-400'"
                                        class="flex flex-col items-center justify-center p-4 rounded-2xl border transition-all">
                                    <i class="fas fa-wallet mb-2"></i>
                                    <span class="text-[10px] font-black uppercase">Digital</span>
                                </button>
                            </div>
                        </div>

                        <div x-show="paymentMethod === 'Cash'" x-transition>
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 block">Cash Received</label>
                            <input type="number" x-model.number="amountTendered" class="w-full pos-field border rounded-2xl px-5 py-4 text-white text-2xl font-black mb-4 focus:outline-none">
                            <div class="grid grid-cols-4 gap-2">
                                <template x-for="val in [5000, 10000, 20000]">
                                    <button @click="amountTendered = val" class="py-2 rounded-xl soft-panel text-[10px] font-black hover:bg-white/10 transition-all" x-text="val.toLocaleString()"></button>
                                </template>
                                <button @click="amountTendered = totals.final" class="py-2 rounded-xl bg-orange-600/20 border border-orange-600/30 text-orange-500 text-[10px] font-black">EXACT</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Change Due -->
                <div x-show="paymentMethod === 'Cash' && amountTendered >= totals.final" class="p-6 bg-emerald-500/10 border border-emerald-500/20 rounded-3xl text-center">
                    <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">Balance Return</p>
                    <h4 class="text-3xl font-black text-white" x-text="formatCurrency(amountTendered - totals.final)"></h4>
                </div>
            </div>

            <div class="p-8 border-t border-white/5">
                <button @click="submitOrder()" 
                        :disabled="processing || (paymentMethod === 'Cash' && (amountTendered === null || amountTendered < totals.final))"
                        class="w-full bg-orange-600 hover:bg-orange-500 disabled:opacity-30 text-white py-5 rounded-[2rem] font-black uppercase tracking-[0.2em] shadow-2xl shadow-orange-600/20 transition-all flex items-center justify-center space-x-3">
                    <span x-show="!processing">Complete Sale</span>
                    <i x-show="processing" class="fas fa-circle-notch fa-spin"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- HELD ORDERS MODAL -->
    <div x-show="showHeldOrders" x-cloak class="fixed inset-0 z-[150] no-print">
        <div class="absolute inset-0 bg-slate-950/85 backdrop-blur-xl" @click="showHeldOrders = false"></div>
        <div class="absolute left-1/2 top-1/2 w-[min(680px,calc(100vw-2rem))] -translate-x-1/2 -translate-y-1/2 rounded-[2rem] glass-panel overflow-hidden">
            <div class="p-6 border-b border-white/5 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-black uppercase tracking-tight text-white">Held Orders</h2>
                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">Pause a sale, serve another customer, then recall it.</p>
                </div>
                <button @click="showHeldOrders = false" class="w-10 h-10 rounded-xl bg-white/5 text-slate-400 hover:text-white hover:bg-white/10 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="max-h-[60vh] overflow-y-auto custom-scrollbar p-4 space-y-3">
                <template x-if="heldOrders.length === 0">
                    <div class="p-12 text-center text-slate-500">
                        <i class="fas fa-inbox text-4xl opacity-30 mb-4"></i>
                        <p class="text-xs font-black uppercase tracking-widest">No held orders</p>
                    </div>
                </template>
                <template x-for="order in heldOrders" :key="order.id">
                    <div class="soft-panel rounded-2xl p-4 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-black text-white truncate" x-text="order.label || 'Counter order'"></p>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">
                                <span x-text="order.itemCount"></span> items · <span x-text="formatCurrency(order.total)"></span> · <span x-text="order.time"></span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button @click="recallHeldOrder(order.id)" class="px-4 py-3 rounded-xl bg-teal-500/15 text-teal-300 hover:bg-teal-500 hover:text-white text-[10px] font-black uppercase tracking-widest transition-all">Recall</button>
                            <button @click="deleteHeldOrder(order.id)" class="w-10 h-10 rounded-xl bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white transition-all">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
    
    <!-- RECEIPT MODAL -->
    <div x-show="showReceiptModal" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-6">
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm"></div>
        <div class="bg-white rounded-[2.5rem] w-full max-w-sm overflow-hidden shadow-2xl relative flex flex-col">
            <div class="voucher-print-area p-8 text-slate-900 bg-gradient-to-b from-slate-50 to-white">
                <div class="text-center mb-8 border-b-2 border-dashed border-slate-200 pb-8">
                    <div class="w-16 h-16 bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-coffee text-white text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-black tracking-tighter">PAICAFE ONLINE</h2>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Transaction Receipt</p>
                    <p class="text-[10px] font-mono text-slate-500 mt-4">Order #<span x-text="receiptDetails.orderId" class="text-slate-900 font-bold"></span></p>
                    <p x-show="receiptDetails.label" class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1" x-text="receiptDetails.label"></p>
                </div>
                
                <div class="space-y-3 mb-8">
                    <template x-for="item in receiptDetails.cart">
                        <div class="flex justify-between items-start text-sm">
                            <div class="flex-grow pr-4">
                                <p class="font-bold leading-tight" x-text="item.name"></p>
                                <p class="text-[10px] text-slate-400" x-text="`${item.quantity} x ${formatCurrency(item.price)}`"></p>
                            </div>
                            <span class="font-black" x-text="formatCurrency(item.price * item.quantity)"></span>
                        </div>
                    </template>
                </div>

                <div class="space-y-2 border-t-2 border-dashed border-slate-200 pt-6">
                    <div class="flex justify-between text-xs font-bold text-slate-500">
                        <span class="uppercase tracking-widest">Subtotal</span>
                        <span x-text="formatCurrency(receiptDetails.totals.subtotal)"></span>
                    </div>
                    <template x-if="receiptDetails.couponDiscount > 0">
                        <div class="flex justify-between text-xs font-bold text-emerald-600">
                            <span class="uppercase tracking-widest underline decoration-wavy underline-offset-4">Voucher Apply</span>
                            <span x-text="'-' + formatCurrency(receiptDetails.couponDiscount)"></span>
                        </div>
                    </template>
                    <div class="flex justify-between text-xs font-bold text-slate-500">
                        <span class="uppercase tracking-widest">Tax Inclusion</span>
                        <span x-text="formatCurrency(receiptDetails.totals.tax)"></span>
                    </div>
                    <div class="flex justify-between text-2xl font-black mt-4 pt-4 border-t border-slate-100">
                        <span>TOTAL</span>
                        <span x-text="formatCurrency(receiptDetails.totals.final)"></span>
                    </div>
                </div>

                <p class="text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-10">Sync Time: <?= date('Y-m-d H:i') ?></p>
            </div>
            
            <div class="p-6 bg-slate-50 border-t border-slate-100 flex gap-4 no-print">
                <button @click="printReceipt()" class="flex-1 bg-slate-900 text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-800 transition-all">Print</button>
                <button @click="startNewOrder()" class="flex-1 bg-white border border-slate-200 text-slate-900 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-50 transition-all">Next Order</button>
            </div>
        </div>
    </div>

<script>
    const PAICAFE_CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;

    function posSystem(categories, taxRate) {
        return {
            loading: true, products: [], categories: categories, selectedCategory: 'All', searchTerm: '', cart: {}, 
            taxRate: taxRate,
            openPaymentModal: false, paymentMethod: 'Cash', customerPhone: '', processing: false, amountTendered: null,
            customerName: '', customerPoints: 0, customerMessage: '',
            showReceiptModal: false, receiptDetails: { orderId: null, cart: [], totals: { subtotal: 0, tax: 0, final: 0 } }, 
            couponCode: '', couponDiscount: 0, couponMessage: '',
            orderLabel: '', orderMode: 'takeaway', showHeldOrders: false, heldOrders: [],
            favoriteProductIds: [],
            darkMode: document.documentElement.classList.contains('dark'),
            
            init() {
                this.darkMode = document.documentElement.classList.contains('dark');
                this.loadHeldOrders();
                this.loadFavorites();
                this.fetchProducts();
                window.addEventListener('paicafe-theme-change', (event) => {
                    this.darkMode = Boolean(event.detail && event.detail.dark);
                });
                window.addEventListener('keydown', (event) => {
                    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                        event.preventDefault();
                        document.querySelector('input[placeholder="Search product..."]')?.focus();
                    }
                    if (event.key === 'F2') {
                        event.preventDefault();
                        this.holdCurrentOrder();
                    }
                    if (event.key === 'F4' && Object.keys(this.cart).length > 0) {
                        event.preventDefault();
                        this.openPaymentModal = true;
                    }
                });
            },

            applyTheme(useDark) {
                document.documentElement.classList.toggle('dark', useDark);
                document.documentElement.classList.toggle('light', !useDark);
                document.documentElement.dataset.theme = useDark ? 'dark' : 'light';
                localStorage.setItem('paicafe-theme', useDark ? 'dark' : 'light');
                localStorage.setItem('darkMode', useDark ? 'true' : 'false');
                this.darkMode = useDark;
                window.dispatchEvent(new CustomEvent('paicafe-theme-change', { detail: { dark: useDark } }));
            },

            toggleTheme() {
                this.applyTheme(!this.darkMode);
            },
            
            fetchProducts() { 
                fetch('api/get_products.php')
                    .then(res => {
                        if (!res.ok) throw new Error('Network fault');
                        return res.json();
                    })
                    .then(data => {
                        if (data.status === 'success') { this.products = data.products; }
                        else { this.showToast('Failed to load products', '#ef4444'); }
                        this.loading = false; 
                    })
                    .catch(err => {
                        console.error('Fetch Error:', err);
                        this.showToast('Connection lost. Please check your internet.', '#ef4444');
                        this.loading = false;
                    });
            },
            
            get filteredProducts() {
                let f = this.products;
                if (this.selectedCategory !== 'All') { f = f.filter(p => p.category_name === this.selectedCategory); }
                if (this.searchTerm.trim() !== '') { f = f.filter(p => p.name_en.toLowerCase().includes(this.searchTerm.toLowerCase())); }
                return f;
            },

            get favoriteProducts() {
                const ids = this.favoriteProductIds.length
                    ? this.favoriteProductIds
                    : Object.values(this.cart).map(item => item.id);
                return ids
                    .map(id => this.products.find(product => String(product.id) === String(id)))
                    .filter(Boolean)
                    .slice(0, 8);
            },

            get cartItemCount() {
                return Object.values(this.cart).reduce((sum, item) => sum + item.quantity, 0);
            },
            
            addToCart(p) { 
                const finalPrice = p.price - (p.price * (p.discount_percentage || 0) / 100);
                if(this.cart[p.id]){this.cart[p.id].quantity++;}
                else{this.cart[p.id]={id:p.id,name:p.name_en,price:parseFloat(finalPrice),quantity:1, image: p.image};}
                this.rememberFavorite(p.id);
            },

            updateQuantity(id, amt) { if(this.cart[id]){this.cart[id].quantity+=amt; if(this.cart[id].quantity<=0){delete this.cart[id];}}},
            manualUpdateQuantity(id, val) { const q = parseInt(val); if(!isNaN(q) && q > 0){this.cart[id].quantity=q;}else{delete this.cart[id];}},
            removeFromCart(id) { delete this.cart[id]; },

            rememberFavorite(id) {
                const normalized = String(id);
                this.favoriteProductIds = [normalized, ...this.favoriteProductIds.filter(itemId => String(itemId) !== normalized)].slice(0, 8);
                localStorage.setItem('paicafe-pos-favorites', JSON.stringify(this.favoriteProductIds));
            },

            loadFavorites() {
                try {
                    const stored = JSON.parse(localStorage.getItem('paicafe-pos-favorites') || '[]');
                    this.favoriteProductIds = Array.isArray(stored) ? stored : [];
                } catch (error) {
                    this.favoriteProductIds = [];
                }
            },

            loadHeldOrders() {
                try {
                    const stored = JSON.parse(localStorage.getItem('paicafe-pos-held-orders') || '[]');
                    this.heldOrders = Array.isArray(stored) ? stored : [];
                } catch (error) {
                    this.heldOrders = [];
                }
            },

            saveHeldOrders() {
                localStorage.setItem('paicafe-pos-held-orders', JSON.stringify(this.heldOrders.slice(0, 12)));
            },

            holdCurrentOrder() {
                if (Object.keys(this.cart).length === 0) {
                    this.showToast('Add items before holding an order', '#64748b');
                    return;
                }
                const heldOrder = {
                    id: Date.now().toString(),
                    label: this.orderLabel.trim() || this.customerName || this.customerPhone || 'Counter order',
                    cart: JSON.parse(JSON.stringify(this.cart)),
                    customerPhone: this.customerPhone,
                    customerName: this.customerName,
                    orderMode: this.orderMode,
                    couponCode: this.couponCode,
                    couponDiscount: this.couponDiscount,
                    total: this.totals.final,
                    itemCount: this.cartItemCount,
                    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                };
                this.heldOrders = [heldOrder, ...this.heldOrders].slice(0, 12);
                this.saveHeldOrders();
                this.resetOrder();
                this.showToast('Order held for later', '#14b8a6');
            },

            recallHeldOrder(id) {
                const heldOrder = this.heldOrders.find(order => order.id === id);
                if (!heldOrder) return;
                if (Object.keys(this.cart).length > 0 && !confirm('Replace the current order with this held order?')) {
                    return;
                }
                this.cart = heldOrder.cart || {};
                this.customerPhone = heldOrder.customerPhone || '';
                this.customerName = heldOrder.customerName || '';
                this.orderMode = heldOrder.orderMode || 'takeaway';
                this.orderLabel = heldOrder.label || '';
                this.couponCode = heldOrder.couponCode || '';
                this.couponDiscount = heldOrder.couponDiscount || 0;
                this.heldOrders = this.heldOrders.filter(order => order.id !== id);
                this.saveHeldOrders();
                this.showHeldOrders = false;
                this.showToast('Held order recalled', '#14b8a6');
            },

            deleteHeldOrder(id) {
                this.heldOrders = this.heldOrders.filter(order => order.id !== id);
                this.saveHeldOrders();
            },
            
            get totals() {
                let subtotal = 0;
                for (let id in this.cart) { subtotal += this.cart[id].price * this.cart[id].quantity; }
                const taxableAmount = subtotal - this.couponDiscount;
                const tax = taxableAmount < 0 ? 0 : taxableAmount * this.taxRate;
                const final = taxableAmount + tax;
                return { subtotal, tax, final };
            },
            
            formatCurrency(amt) { 
                return new Intl.NumberFormat('en-US',{style:'currency',currency:'MMK', minimumFractionDigits: 0}).format(amt || 0).replace('MMK', 'Ks');
            },

            applyCoupon() {
                if(!this.couponCode) return;
                this.couponMessage = 'Verifying...';
                fetch('api/apply_coupon.php', { 
                    method: 'POST', headers: {'Content-Type':'application/json'}, 
                    body: JSON.stringify({ code: this.couponCode, subtotal: this.totals.subtotal })
                })
                .then(res => res.json())
                .then(data => {
                    this.couponMessage = data.message;
                    if (data.status === 'success') { 
                        this.couponDiscount = data.discount; 
                        this.showToast('Voucher Authenticated', '#10b981');
                    } else { this.couponDiscount = 0; }
                });
            },

            findCustomer() {
                if (!this.customerPhone.trim()) return;
                this.customerMessage = 'Locating...';
                fetch(`api/get_user_by_phone.php?phone=${encodeURIComponent(this.customerPhone)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            this.customerName = data.user.username;
                            this.customerPoints = data.user.loyalty_points;
                            this.customerMessage = `ID: ${this.customerName.toUpperCase()} / ${this.customerPoints} PTS`;
                            this.showToast('Customer Identified', '#3b82f6');
                        } else {
                            this.customerName = '';
                            this.customerMessage = 'No saved customer found. This sale can continue.';
                        }
                    });
            },

            submitOrder() {
                this.processing = true;
                fetch('api/submit_pos_order.php', { 
                    method: 'POST', headers: {'Content-Type':'application/json'}, 
                    body: JSON.stringify({ 
                        cart: this.cart, 
                        payment_method: this.paymentMethod, 
                        customer_phone: this.customerPhone,
                        coupon_code: this.couponCode,
                        csrf_token: PAICAFE_CSRF_TOKEN,
                        discount_amount: this.couponDiscount,
                        order_label: this.orderLabel,
                        order_mode: this.orderMode
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        this.receiptDetails = { 
                            orderId: data.order_id, cart: Object.values(this.cart), 
                            totals: JSON.parse(JSON.stringify(this.totals)), 
                            couponCode: this.couponCode, couponDiscount: this.couponDiscount,
                            label: this.orderLabel
                        };
                        this.showReceiptModal = true;
                        this.showToast('Transaction Authorized', '#10b981');
                    } else { alert('Error: ' + data.message); }
                })
                .finally(() => { this.processing = false; this.openPaymentModal = false; });
            },

            resetOrder() { 
                this.cart={}; this.paymentMethod='Cash'; this.customerPhone=''; this.amountTendered=null; 
                this.couponCode=''; this.couponDiscount=0; this.couponMessage=''; 
                this.customerName=''; this.customerPoints=0; this.customerMessage=''; this.orderLabel=''; this.orderMode='takeaway';
            },

            printReceipt() {
                if (this.receiptDetails.orderId) {
                    const url = `print_receipt.php?order_id=${this.receiptDetails.orderId}`;
                    window.open(url, '_blank', 'width=400,height=700');
                }
            },
            
            startNewOrder() { 
                this.showReceiptModal = false; 
                this.receiptDetails = { orderId: null, cart: [], totals: { subtotal: 0, tax: 0, final: 0 } };
                this.resetOrder();
            },

            showToast(msg, color) {
                Toastify({
                    text: msg,
                    duration: 3000,
                    gravity: "top",
                    position: "center",
                    style: { background: color, borderRadius: "12px", fontBold: "true", fontSize: "10px", tracking: "0.1em" }
                }).showToast();
            }
        }
    }
</script>

</body>
</html>
