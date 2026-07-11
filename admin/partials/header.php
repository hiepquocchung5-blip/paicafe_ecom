<?php
// We must go UP TWO directories from /admin/partials/ to get to the root
// and then go into /includes/
require_once dirname(__DIR__, 2) . '/includes/db_connect.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// This function is now loaded from the master functions.php file
require_admin_login();
require_csrf_token();
start_admin_csrf_form_injection();
$admin_asset_base = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') === 0) ? '/admin' : '';
$tailwind_css = load_tailwind_css([
    dirname(__DIR__) . '/assets/css/tailwind.css',
    dirname(__DIR__, 2) . '/assets/css/tailwind.css',
]);
?>
<!DOCTYPE html>
<html lang="en" class="h-full" 
      x-data="{ 
        sidebarOpen: false, 
        darkMode: window.PaicafeTheme ? window.PaicafeTheme.isDark() : document.documentElement.classList.contains('dark')
      }" 
      x-init="$watch('darkMode', val => window.PaicafeTheme ? window.PaicafeTheme.set(val) : (document.documentElement.classList.toggle('dark', val), document.documentElement.classList.toggle('light', !val), document.documentElement.dataset.theme = val ? 'dark' : 'light'))"
      :class="{ 'dark': darkMode, 'light': !darkMode }">
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Poppins:wght@400;600;700&display=swap">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAICAFE Admin Hub</title>
    <script>
        (function () {
            const storedTheme = localStorage.getItem('paicafe-theme') || localStorage.getItem('darkMode');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const useDark = storedTheme ? (storedTheme === 'dark' || storedTheme === 'true') : prefersDark;
            document.documentElement.classList.toggle('dark', useDark);
            document.documentElement.classList.toggle('light', !useDark);
            document.documentElement.dataset.theme = useDark ? 'dark' : 'light';
        })();
    </script>
    <style><?= $tailwind_css ?></style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $admin_asset_base ?>/assets/css/style.css">
    <script src="<?= $admin_asset_base ?>/assets/js/theme.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="liquid-glass-v2 h-full bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-300">
<div id="admin-page-loader" class="admin-page-loader" aria-hidden="true">
    <div class="admin-coffee-loader">
        <svg class="admin-coffee-loader__svg" viewBox="0 0 260 210" role="img" aria-label="Loading PAICAFE admin">
            <defs>
                <linearGradient id="adminLoaderGlass" x1="0" x2="1" y1="0" y2="1">
                    <stop offset="0%" stop-color="#ffffff" stop-opacity="0.78"/>
                    <stop offset="100%" stop-color="#ccfbf1" stop-opacity="0.22"/>
                </linearGradient>
                <linearGradient id="adminLoaderCoffee" x1="0" x2="0" y1="1" y2="0">
                    <stop offset="0%" stop-color="#7c2d12"/>
                    <stop offset="48%" stop-color="#b45309"/>
                    <stop offset="100%" stop-color="#f59e0b"/>
                </linearGradient>
            </defs>
            <g class="admin-coffee-loader__steam" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round">
                <path d="M95 52c-10-12 10-17 0-30"/>
                <path d="M130 48c-10-12 10-17 0-30"/>
                <path d="M165 52c-10-12 10-17 0-30"/>
            </g>
            <g class="admin-coffee-loader__pot">
                <path d="M50 78h70c13 0 24 11 24 24v22c0 22-18 40-40 40H67c-22 0-40-18-40-40v-22c0-13 10-24 23-24Z" fill="rgba(15,118,110,0.18)" stroke="currentColor" stroke-width="7"/>
                <path d="M144 102c28-2 37 34 8 44" fill="none" stroke="currentColor" stroke-width="11" stroke-linecap="round"/>
                <path d="M35 78l-16-20h45" fill="none" stroke="currentColor" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M58 100h52" stroke="rgba(255,255,255,0.55)" stroke-width="5" stroke-linecap="round"/>
            </g>
            <path class="admin-coffee-loader__pour" d="M146 94c24 24 31 48 30 73" fill="none" stroke="#d97706" stroke-width="8" stroke-linecap="round"/>
            <g class="admin-coffee-loader__glass">
                <path d="M165 92h62l-10 92c-1 11-11 19-22 19h-1c-11 0-21-8-22-19l-7-92Z" fill="url(#adminLoaderGlass)" stroke="rgba(255,255,255,0.75)" stroke-width="6"/>
                <clipPath id="adminLoaderCupClip">
                    <path d="M168 96h56l-9 86c-1 9-9 16-18 16h-3c-9 0-17-7-18-16l-8-86Z"/>
                </clipPath>
                <g clip-path="url(#adminLoaderCupClip)">
                    <rect class="admin-coffee-loader__fill" x="162" y="198" width="70" height="100" fill="url(#adminLoaderCoffee)"/>
                    <path d="M164 121c12 7 22-7 34 0s20-5 32 1v16h-66Z" fill="rgba(255,255,255,0.2)"/>
                </g>
                <path d="M178 114h34" stroke="rgba(255,255,255,0.52)" stroke-width="5" stroke-linecap="round"/>
            </g>
        </svg>
        <div class="admin-coffee-loader__copy">
            <span>PAICAFE ADMIN</span>
            <strong>Preparing dashboard</strong>
        </div>
    </div>
</div>
<div class="flex h-full overflow-hidden">
    
    <!-- Sidebar -->
    <div class="hidden lg:flex lg:flex-shrink-0">
        <div class="admin-sidebar flex flex-col w-72 text-white">
            <?php include 'sidebar_content.php'; ?>
        </div>
    </div>

    <!-- Mobile Sidebar -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 flex z-40 lg:hidden" 
         x-cloak>
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" @click="sidebarOpen = false"></div>
        <div x-show="sidebarOpen"
             x-transition:enter="transition ease-in-out duration-300 transform"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-300 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="admin-sidebar relative flex-1 flex flex-col max-w-xs w-full text-white shadow-2xl">
            <div class="absolute top-0 right-0 -mr-12 pt-4">
                <button @click="sidebarOpen = false" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                    <i class="fas fa-times text-white"></i>
                </button>
            </div>
            <?php include 'sidebar_content.php'; ?>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden relative">
        <header class="admin-shell-header bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 px-8 py-4 flex justify-between items-center z-10 transition-colors duration-300">
            <div class="flex items-center space-x-4">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 -ml-2 text-slate-500 hover:text-slate-600 focus:outline-none">
                    <i class="fas fa-bars-staggered text-xl"></i>
                </button>
                <div class="hidden sm:block">
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1">Authenticated Session</p>
                    <h2 class="text-lg font-black text-slate-800 dark:text-slate-100 tracking-tight leading-none">
                        Welcome back, <?= e($_SESSION['admin_username']) ?>
                    </h2>
                </div>
            </div>
            
            <div class="flex items-center space-x-6">
                <!-- Theme Toggle -->
                <button @click="darkMode = !darkMode" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-orange-500 transition-all shadow-sm border border-slate-200 dark:border-slate-700" aria-label="Toggle color theme" title="Toggle theme">
                    <i class="fas" :class="darkMode ? 'fa-sun text-yellow-500' : 'fa-moon text-blue-500'"></i>
                </button>

                <div class="hidden md:flex flex-col items-end">
                    <span class="text-[10px] font-black uppercase tracking-widest text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-100 dark:border-emerald-500/20">
                        System Active
                    </span>
                </div>
                <div class="h-10 w-10 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center border border-slate-200 dark:border-slate-700">
                    <i class="fas fa-user-shield text-slate-400 dark:text-slate-500"></i>
                </div>
            </div>
        </header>
        
        <main class="admin-shell-main flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 dark:bg-slate-950 custom-scrollbar relative transition-colors duration-300">
            <div class="absolute inset-0 bg-[radial-gradient(rgba(15,118,110,0.16)_1px,transparent_1px)] dark:bg-[radial-gradient(rgba(94,234,212,0.12)_1px,transparent_1px)] [background-size:24px_24px] opacity-60 pointer-events-none"></div>
            <div class="relative z-0 p-8">

