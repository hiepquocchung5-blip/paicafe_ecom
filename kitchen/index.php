<?php
// By including functions.php first, we guarantee the session is started.
// Path corrected to reach root includes from /kitchen/ subdirectory
require_once  'includes/functions.php';

require_kitchen_login();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pai Cafe | Kitchen Display</title>
    <meta name="robots" content="noindex, nofollow, noarchive">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#c2410c">
    
    <!-- Fonts & Styles -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&family=JetBrains+Mono:wght@500;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://paicafes.com/assets/css/tailwind.css?v=<?= filemtime(__DIR__ . '/../assets/css/tailwind.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    
    <!-- Alpine.js with defer optimization -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        :root {
            --brand-primary: #EA580C;
            --brand-secondary: #F97316;
            --bg-dark: #171411;
            --card-bg: #24201c;
            --font-sans: 'Poppins', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--bg-dark);
            color: #e5e7eb;
            background-image: radial-gradient(circle at 15% 0%, rgba(234,88,12,.12), transparent 32%);
            overflow-x: hidden;
        }

        /* Futuristic Scrollbar */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: #0a0c10; }
        ::-webkit-scrollbar-thumb { background: var(--brand-primary); border-radius: 10px; }
        
        .glass-panel {
            background: var(--card-bg);
            border: 1px solid rgba(255, 245, 230, 0.08);
            box-shadow: 0 14px 38px rgba(0, 0, 0, 0.28);
        }

        [x-cloak] { display: none !important; }
        .ticket-pending {
            background: linear-gradient(180deg, rgba(245, 158, 11, 0.08), var(--card-bg) 28%);
        }
        .ticket-preparing {
            background: linear-gradient(180deg, rgba(16, 185, 129, 0.06), var(--card-bg) 28%);
        }
        .queue-count { min-width: 1.5rem; }
        .feed-ok { background: rgba(16, 185, 129, .05); border-color: rgba(16, 185, 129, .14); color: #6ee7b7; }
        .feed-error { background: rgba(239, 68, 68, .10); border-color: rgba(239, 68, 68, .22); color: #fca5a5; }
        .feed-dot-ok { background: #34d399; }
        .feed-dot-error { background: #f87171; }
        .status-strip-pending { background: #f59e0b; }
        .status-strip-preparing { background: #10b981; }
        .status-badge-pending { background: rgba(245, 158, 11, .15); border: 1px solid rgba(245, 158, 11, .24); color: #fbbf24; }
        .status-badge-preparing { background: rgba(16, 185, 129, .15); border: 1px solid rgba(16, 185, 129, .24); color: #6ee7b7; }
        .action-accept { background: #ea580c; box-shadow: 0 0 24px rgba(234, 88, 12, .28); }
        .action-accept:hover { background: #f97316; }
        .action-ready { background: #059669; color: white; box-shadow: 0 0 20px rgba(16, 185, 129, .30); }
        .action-ready:hover { background: #10b981; }
        .action-disabled { background: rgba(255, 255, 255, .05); color: #6b7280; cursor: not-allowed; }
        button:disabled { opacity: .62; }

        /* Tactical Priority States */
        .status-fresh { border-left: 5px solid #10b981; }
        .status-warning { border-left: 5px solid var(--brand-primary); }
        .status-critical { 
            border-left: 5px solid #ef4444; 
            animation: thermal-pulse 1.5s infinite ease-in-out;
        }

        @keyframes thermal-pulse {
            0% { box-shadow: inset 0 0 15px rgba(239, 68, 68, 0.1); }
            50% { box-shadow: inset 0 0 30px rgba(239, 68, 68, 0.3); border-left-color: #7f1d1d; }
            100% { box-shadow: inset 0 0 15px rgba(239, 68, 68, 0.1); }
        }

        .item-prepped {
            opacity: 0.25;
            text-decoration: line-through;
            filter: grayscale(1);
            transform: scale(0.98);
        }

        .order-exit {
            animation: slideOutRight 0.5s forwards cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideOutRight {
            to { opacity: 0; transform: translateX(150px) rotate(2deg); }
        }

        button, .glass-panel { border-radius: 18px; }
    </style>
</head>
<body x-data="kitchenOS()">

    <!-- Audio Link for New Alerts -->
    <audio id="alert-ping" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

    <!-- Kitchen header -->
    <header class="sticky top-0 z-50 glass-panel border-b border-white/5">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-5">
                <div class="relative group">
                    <div class="absolute -inset-1 bg-orange-600 rounded-lg blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
                    <div class="relative p-1 bg-black rounded-xl border border-orange-600/30"><img src="https://paicafes.com/assets/svg/pai-mark.svg" alt="Pai Cafe" class="w-10 h-10"></div>
                </div>
                <div>
                    <h1 class="text-lg font-black tracking-tight text-white leading-none">Pai Cafe <span class="text-orange-500">Kitchen</span></h1>
                    <div class="flex items-center space-x-2 mt-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <p class="text-[10px] text-gray-400 tracking-wide">Live order preparation</p>
                    </div>
                </div>
            </div>

            <!-- Throughput Capacity Bar -->
            <div class="hidden lg:flex items-center space-x-8 px-8 border-x border-white/5">
                <div class="w-48">
                    <div class="flex justify-between text-[9px] font-bold uppercase tracking-widest text-gray-500 mb-1">
                        <span>Kitchen load</span>
                        <span x-text="loadPercent + '%'"></span>
                    </div>
                    <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden">
                        <div class="h-full transition-all duration-1000" 
                             :class="loadPercent > 80 ? 'bg-red-500' : 'bg-orange-500'" 
                             :style="`width: ${loadPercent}%` "></div>
                    </div>
                </div>
                <div class="text-center">
                    <p class="text-[9px] text-gray-500 uppercase font-black tracking-tighter">Items Total</p>
                    <p class="text-sm font-mono font-black text-white" x-text="totalItemsInQueue"></p>
                </div>
            </div>
            
            <div class="flex items-center space-x-6">
                <button @click="soundEnabled=!soundEnabled; localStorage.setItem('kitchen-sound',soundEnabled?'1':'0')" class="p-3 rounded-xl bg-white/5 text-gray-300" :title="soundEnabled?'Mute alerts':'Enable alerts'"><i class="fas" :class="soundEnabled?'fa-volume-high':'fa-volume-xmark'"></i></button>
                <button @click="toggleFullscreen()" class="p-3 rounded-xl bg-white/5 text-gray-300" title="Fullscreen"><i class="fas fa-expand"></i></button>
                <div class="hidden sm:block text-right font-mono">
                    <p class="text-[10px] text-gray-500 font-bold uppercase" x-text="currentDate"></p>
                    <p class="text-base font-black text-white tracking-widest" x-text="currentTime"></p>
                </div>
                <div class="flex items-center space-x-3 bg-white/5 rounded-xl p-2 border border-white/5">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['admin_username']) ?>&background=EA580C&color=fff" class="w-8 h-8 rounded-lg">
                    <div class="hidden xl:block leading-none pr-2">
                        <p class="text-[9px] text-orange-500 font-black uppercase"><?= e($_SESSION['user_type']) ?></p>
                        <p class="text-xs font-bold text-white"><?= e($_SESSION['admin_username']) ?></p>
                    </div>
                </div>
                <a href="/logout.php" class="p-3 rounded-xl bg-red-500/5 text-red-500 hover:bg-red-500 hover:text-white transition-all border border-red-500/10">
                    <i class="fas fa-power-off"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Command Center Grid -->
    <main class="container mx-auto p-6 pt-8">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 mb-7">
            <div class="flex flex-wrap gap-2 bg-black/20 p-1.5 rounded-2xl" role="tablist" aria-label="Kitchen queue status">
                <template x-for="tab in queueTabs" :key="tab.value">
                    <button type="button" role="tab" :aria-selected="stageFilter === tab.value" @click="stageFilter=tab.value" class="px-4 py-2.5 rounded-xl text-sm font-bold transition-colors flex items-center gap-2" :class="stageFilter===tab.value?'bg-orange-600 text-white shadow-lg':'text-gray-400 hover:text-white'">
                        <span x-text="tab.label"></span>
                        <span class="queue-count h-6 px-1.5 rounded-lg text-[11px] font-mono inline-flex items-center justify-center" :class="stageFilter===tab.value?'bg-white/20':'bg-white/5'" x-text="tab.count"></span>
                    </button>
                </template>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 px-3 py-2 rounded-xl border" aria-live="polite" :class="feedError?'feed-error':'feed-ok'">
                    <span class="w-2 h-2 rounded-full" :class="feedError?'feed-dot-error':'feed-dot-ok animate-pulse'"></span>
                    <span class="text-[10px] font-bold uppercase tracking-wider" x-text="feedError || (lastSynced ? 'Live · updated ' + lastSynced : 'Connecting…')"></span>
                    <button x-show="feedError" type="button" @click="syncFeed()" class="text-[10px] font-black underline">Retry</button>
                </div>
                <div class="flex items-center gap-2"><span class="text-xs text-gray-500 font-bold uppercase">Station</span><select x-model="stationFilter" class="bg-[#24201c] border border-white/10 rounded-xl px-4 py-2.5 text-sm"><option>All</option><option>Kitchen</option><option>Bar</option><option>Dessert</option></select></div>
            </div>
        </div>
        
        <!-- Loading UI -->
        <div x-show="loading && orders.length === 0" class="flex flex-col items-center justify-center py-40">
            <div class="relative w-20 h-20">
                <div class="absolute inset-0 border-4 border-orange-600/10 border-t-orange-600 rounded-full animate-spin"></div>
                <div class="absolute inset-2 border-4 border-emerald-500/10 border-b-emerald-500 rounded-full animate-[spin_2s_linear_infinite_reverse]"></div>
            </div>
                    <p class="mt-8 text-orange-400 text-sm font-semibold">Loading kitchen orders…</p>
        </div>

        <!-- Empty Desk -->
        <div x-cloak x-show="!loading && visibleOrders.length === 0" class="flex flex-col items-center justify-center py-40">
            <div class="w-24 h-24 bg-white/5 rounded-full flex items-center justify-center border border-white/5 mb-8">
                <i class="fas fa-check-circle text-4xl text-emerald-500 opacity-40"></i>
            </div>
            <h2 class="text-2xl font-semibold text-gray-300" x-text="orders.length ? 'No orders in this view' : 'All caught up'"></h2>
            <p class="text-gray-500 mt-2" x-text="orders.length ? 'Choose another queue or station.' : 'New orders will appear here automatically.'"></p>
        </div>

        <!-- Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-8">
            <template x-for="order in visibleOrders" :key="order.id">
                
                <div :id="'order-' + order.id" 
                     class="glass-panel rounded-2xl flex flex-col transition-all duration-300 relative overflow-hidden group"
                     :class="[getPriorityClass(order.created_at), order.status === 'pending_approval' ? 'ticket-pending' : 'ticket-preparing']">

                    <div class="h-1.5 w-full" :class="order.status === 'pending_approval' ? 'status-strip-pending' : 'status-strip-preparing'"></div>
                    
                    <!-- Card ID & Timer -->
                    <div class="px-5 py-4 flex justify-between items-start bg-white/[0.02] border-b border-white/5">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="w-2 h-2 rounded-full bg-orange-500 animate-ping" x-show="isCritical(order.created_at)"></span>
                                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest" x-text="order.order_type === 'qr' ? 'Table order' : (order.order_type === 'pos' ? 'POS order' : 'Online order')"></span>
                            </div>
                            <h2 class="text-4xl font-black text-white tracking-tighter mt-1">#<span x-text="order.id"></span></h2>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest mb-2" :class="order.status === 'pending_approval' ? 'status-badge-pending' : 'status-badge-preparing'">
                                <i class="fas mr-1.5" :class="order.status === 'pending_approval' ? 'fa-bell' : 'fa-fire-burner'"></i>
                                <span x-text="order.status === 'pending_approval' ? 'Pending' : 'Preparing'"></span>
                            </span>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest block mb-1">Waiting</span>
                            <span class="text-xl font-mono font-black tracking-tighter" :class="getTimeColor(order.created_at)" x-text="getTimeDiff(order.created_at)"></span>
                        </div>
                    </div>

                    <!-- Origin Tag -->
                    <div class="px-5 pt-4 flex justify-between items-center">
                        <div class="inline-flex items-center px-3 py-1 bg-white/5 rounded-lg border border-white/5">
                            <i class="fas mr-2 text-[10px] opacity-60" :class="order.table_number ? 'fa-chair' : 'fa-globe-americas'"></i>
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-300" x-text="order.table_number ? 'Table ' + order.table_number : 'Web Order'"></span>
                        </div>
                        <template x-if="isCritical(order.created_at)">
                            <span class="text-[10px] font-black text-red-500 uppercase animate-pulse">!! Delayed !!</span>
                        </template>
                    </div>

                    <!-- Interactive Item List -->
                    <div class="p-5 flex-grow">
                        <div class="space-y-3">
                            <template x-for="(item, index) in order.items" :key="index">
                                <button type="button" @click="toggleItemStatus(order, index)" :disabled="order.status !== 'processing'"
                                     class="w-full text-left flex items-center p-3 rounded-xl border border-white/5 transition-all select-none"
                                     :class="order.status !== 'processing' ? 'bg-black/10 cursor-default' : (isPrepped(order.id, index) ? 'item-prepped bg-black/50' : 'bg-white/[0.03] hover:bg-white/10 cursor-pointer')">
                                    
                                    <div class="w-8 h-8 flex-shrink-0 rounded-lg flex items-center justify-center mr-4 border transition-colors"
                                         :class="isPrepped(order.id, index) ? 'bg-emerald-500 border-emerald-500 text-black' : 'bg-orange-600/20 border-orange-600/40 text-orange-500'">
                                        <span x-show="!isPrepped(order.id, index)" class="text-sm font-black" x-text="item.quantity"></span>
                                        <i x-show="isPrepped(order.id, index)" class="fas fa-check text-xs"></i>
                                    </div>
                                    
                                    <div class="flex-grow">
                                        <p class="text-sm font-bold text-white leading-tight" x-text="item.name_en"></p>
                                        <template x-if="item.notes">
                                            <p class="text-[10px] text-red-400 font-medium italic mt-1" x-text="'* ' + item.notes"></p>
                                        </template>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Bottom Action -->
                    <div class="p-5 bg-black/40 border-t border-white/5">
                        <button @click="printTicket(order)" class="w-full mb-2 py-2 text-xs font-bold text-gray-400 hover:text-white"><i class="fas fa-print mr-2"></i>Print ticket</button>
                        <template x-if="order.status === 'pending_approval'">
                            <button type="button" @click="acceptOrder(order)" :disabled="isBusy(order.id)"
                                    class="action-accept w-full py-4 rounded-xl text-white font-black uppercase tracking-widest text-[11px] transition-all active:scale-95 flex items-center justify-center">
                                <i class="fas mr-2" :class="isBusy(order.id) ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                                <span x-text="isBusy(order.id) ? 'Accepting…' : 'Accept order'"></span>
                            </button>
                        </template>
                        <template x-if="order.status === 'processing'">
                            <button type="button" @click="bumpOrder(order)" :disabled="!allPrepped(order) || isBusy(order.id)"
                                    class="w-full py-4 rounded-xl font-black uppercase tracking-widest text-[11px] transition-all active:scale-95 flex items-center justify-center"
                                    :class="allPrepped(order) ? 'action-ready' : 'action-disabled'">
                                <i class="fas mr-2 text-[10px]" :class="isBusy(order.id) ? 'fa-spinner fa-spin' : (allPrepped(order) ? 'fa-check-double' : 'fa-list-check')"></i>
                                <span x-text="isBusy(order.id) ? 'Updating…' : (allPrepped(order) ? 'Mark ready' : 'Complete items first')"></span>
                            </button>
                        </template>
                    </div>
                </div>

            </template>
        </div>
    </main>

    <script>
        function kitchenOS() {
            return {
                orders: [],
                loading: true,
                currentTime: '',
                currentDate: '',
                now: Date.now(),
                lastPacketIds: new Set(),
                prepLocal: {}, // { orderId: [preppedIndices] }
                busyOrders: {},
                stageFilter: 'all',
                stationFilter: 'All',
                soundEnabled: localStorage.getItem('kitchen-sound') !== '0',
                syncing: false,
                feedError: '',
                lastSynced: '',
                
                init() {
                    this.refreshClock();
                    setInterval(() => {
                        this.refreshClock();
                        this.now = Date.now();
                    }, 1000);
                    
                    this.syncFeed();
                    setInterval(() => this.syncFeed(), 5000);
                },

                get sortedOrders() {
                    // Waiting acknowledgements stay ahead of in-progress tickets.
                    return [...this.orders].sort((a, b) => {
                        if (a.status !== b.status) return a.status === 'pending_approval' ? -1 : 1;
                        return new Date(a.created_at) - new Date(b.created_at);
                    });
                },
                get pendingCount() {
                    return this.orders.filter(order => order.status === 'pending_approval').length;
                },
                get preparingCount() {
                    return this.orders.filter(order => order.status === 'processing').length;
                },
                get queueTabs() {
                    return [
                        { value: 'all', label: 'All orders', count: this.orders.length },
                        { value: 'pending', label: 'Pending', count: this.pendingCount },
                        { value: 'preparing', label: 'Preparing', count: this.preparingCount },
                    ];
                },
                get visibleOrders() {
                    return this.sortedOrders.filter(order => {
                        const stageMatch = this.stageFilter === 'all'
                            || (this.stageFilter === 'pending' && order.status === 'pending_approval')
                            || (this.stageFilter === 'preparing' && order.status === 'processing');
                        if (!stageMatch || this.stationFilter === 'All') return stageMatch;
                        return order.items.some(item => {
                            const category = String(item.category_name || '').toLowerCase();
                            if (this.stationFilter === 'Bar') return /coffee|drink|beverage|juice|smoothie|latte/.test(category);
                            if (this.stationFilter === 'Dessert') return /dessert|cake|sweet|bakery/.test(category);
                            return !/coffee|drink|beverage|juice|smoothie|latte|dessert|cake|sweet|bakery/.test(category);
                        });
                    });
                },

                get totalItemsInQueue() {
                    return this.orders.reduce((total, order) => total + order.items.reduce((sum, item) => sum + Number(item.quantity || 0), 0), 0);
                },

                get loadPercent() {
                    const capacity = 30; // Max ideal items for this station
                    const percent = (this.totalItemsInQueue / capacity) * 100;
                    return Math.min(Math.round(percent), 100);
                },
                
                refreshClock() {
                    const d = new Date();
                    this.currentTime = d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    this.currentDate = d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });
                },

                getTimeDiff(createdAt) {
                    const diff = Math.floor((this.now - new Date(createdAt).getTime()) / 1000);
                    const mm = Math.floor(diff / 60);
                    const ss = diff % 60;
                    return `${mm}:${ss.toString().padStart(2, '0')}`;
                },

                getPriorityClass(createdAt) {
                    const mins = (this.now - new Date(createdAt).getTime()) / 60000;
                    if (mins < 5) return 'status-fresh';
                    if (mins < 12) return 'status-warning';
                    return 'status-critical';
                },

                getTimeColor(createdAt) {
                    const mins = (this.now - new Date(createdAt).getTime()) / 60000;
                    if (mins < 5) return 'text-emerald-500';
                    if (mins < 12) return 'text-orange-500';
                    return 'text-red-500';
                },

                isCritical(createdAt) {
                    return ((this.now - new Date(createdAt).getTime()) / 60000) >= 12;
                },

                // Item Tracking Logic
                toggleItemStatus(order, idx) {
                    if (order.status !== 'processing') return;
                    const orderId = order.id;
                    if (!this.prepLocal[orderId]) this.prepLocal[orderId] = [];
                    const pos = this.prepLocal[orderId].indexOf(idx);
                    if (pos > -1) {
                        this.prepLocal[orderId].splice(pos, 1);
                    } else {
                        this.prepLocal[orderId].push(idx);
                    }
                },

                isPrepped(orderId, idx) {
                    return this.prepLocal[orderId] && this.prepLocal[orderId].includes(idx);
                },

                allPrepped(order) {
                    return order.items.length > 0 && this.prepLocal[order.id] && this.prepLocal[order.id].length === order.items.length;
                },

                isBusy(orderId) {
                    return Boolean(this.busyOrders[orderId]);
                },

                syncFeed() {
                    if (this.syncing) return;
                    this.syncing = true;
                    fetch('/api/get_new_orders.php')
                        .then(async res => {
                            const data = await res.json();
                            if (!res.ok || data.status === 'error') throw new Error(data.message || 'Unable to refresh orders.');
                            return data;
                        })
                        .then(data => {
                            const incoming = Array.isArray(data) ? data : (data.orders || []);
                            
                            // Protocol for incoming packets
                            if (this.lastPacketIds.size > 0) {
                                incoming.forEach(o => {
                                    if (!this.lastPacketIds.has(o.id)) this.triggerIncomingAlert(o.id);
                                });
                            }
                            
                            this.orders = incoming;
                            this.lastPacketIds = new Set(incoming.map(o => o.id));
                            this.feedError = '';
                            this.lastSynced = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
                            this.loading = false;
                        })
                        .catch(err => {
                            console.error('Kitchen order sync failed:', err);
                            this.feedError = err.message || 'Queue offline';
                            this.loading = false;
                        })
                        .finally(() => { this.syncing = false; });
                },

                triggerIncomingAlert(id) {
                    if (this.soundEnabled) document.getElementById('alert-ping').play().catch(() => {});
                    Toastify({
                        text: `New kitchen order #${id}`,
                        duration: 5000,
                        gravity: "top",
                        position: "right",
                        style: {
                            background: "linear-gradient(to right, #EA580C, #F97316)",
                            fontWeight: "700",
                            borderRadius: "14px",
                            boxShadow: "0 10px 30px rgba(234, 88, 12, 0.4)"
                        }
                    }).showToast();
                },

                toggleFullscreen() {
                    if (!document.fullscreenElement) document.documentElement.requestFullscreen?.(); else document.exitFullscreen?.();
                },

                printTicket(order) {
                    const items = order.items.map(item => `${item.quantity} × ${item.name_en}`).join('\n');
                    const popup = window.open('', '_blank', 'width=420,height=650');
                    if (!popup) return;
                    popup.document.write(`<title>Order #${order.id}</title><style>body{font-family:system-ui;padding:24px}h1{font-size:28px}pre{font:16px/1.8 system-ui;white-space:pre-wrap}</style><h1>Pai Cafe · Order #${order.id}</h1><p>${order.table_number ? 'Table '+order.table_number : 'Web order'}</p><pre>${items.replace(/[&<>]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]))}</pre>`);
                    popup.document.close(); popup.focus(); popup.print();
                },

                showToast(message, type = 'success') {
                    Toastify({
                        text: message,
                        duration: 3500,
                        gravity: 'top',
                        position: 'right',
                        style: {
                            background: type === 'error' ? '#b91c1c' : '#047857',
                            fontWeight: '700',
                            borderRadius: '12px'
                        }
                    }).showToast();
                },

                async acceptOrder(order) {
                    if (order.status !== 'pending_approval' || this.isBusy(order.id)) return;
                    this.busyOrders[order.id] = true;

                    try {
                        const response = await fetch('/api/accept_order.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ order_id: order.id })
                        });
                        const data = await response.json();
                        if (!response.ok || data.status !== 'success') throw new Error(data.message || 'Could not accept order.');

                        order.status = 'processing';
                        order.updated_at = new Date().toISOString();
                        this.showToast(`Order #${order.id} accepted — start preparing.`);
                    } catch (error) {
                        this.showToast(error.message || 'Could not accept order.', 'error');
                        this.syncFeed();
                    } finally {
                        delete this.busyOrders[order.id];
                    }
                },

                async bumpOrder(order) {
                    const id = order.id;
                    if (!this.allPrepped(order) || this.isBusy(id)) return;
                    this.busyOrders[id] = true;
                    const el = document.getElementById('order-' + id);

                    try {
                        const response = await fetch('/api/complete_order.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ order_id: id })
                        });
                        const data = await response.json();
                        if (!response.ok || data.status !== 'success') throw new Error(data.message || 'Could not mark order ready.');

                        if (el) el.classList.add('order-exit');
                        this.showToast(`Order #${id} is ready for pickup.`);
                        setTimeout(() => {
                            this.orders = this.orders.filter(o => o.id !== id);
                            delete this.prepLocal[id];
                            delete this.busyOrders[id];
                        }, 500);
                    } catch (error) {
                        if (el) el.classList.remove('order-exit');
                        delete this.busyOrders[id];
                        this.showToast(error.message || 'Could not mark order ready.', 'error');
                        this.syncFeed();
                    }
                }
            }
        }
    </script>
    <script>
    if ('serviceWorker' in navigator) window.addEventListener('load',()=>navigator.serviceWorker.register('/sw.js').then(reg=>reg.addEventListener('updatefound',()=>{const worker=reg.installing;worker&&worker.addEventListener('statechange',()=>{if(worker.state==='installed'&&navigator.serviceWorker.controller&&confirm('Kitchen display update ready. Reload now?'))location.reload();});})).catch(()=>{}));
    </script>
</body>
</html>
