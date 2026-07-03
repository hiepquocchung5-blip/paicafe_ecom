<?php
// We must go UP TWO directories from /admin/partials/ to get to the root
// and then go into /includes/
require_once dirname(__DIR__, 2) . '/includes/db_connect.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// This function is now loaded from the master functions.php file
require_admin_login();
$admin_asset_base = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') === 0) ? '/admin' : '';
?>
<!DOCTYPE html>
<html lang="en" class="h-full" 
      x-data="{ 
        sidebarOpen: false, 
        darkMode: window.PaicafeTheme ? window.PaicafeTheme.isDark() : document.documentElement.classList.contains('dark')
      }" 
      x-init="$watch('darkMode', val => window.PaicafeTheme ? window.PaicafeTheme.set(val) : document.documentElement.classList.toggle('dark', val))"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAICAFE Control Center</title>
    <script>
        (function () {
            const storedTheme = localStorage.getItem('paicafe-theme') || localStorage.getItem('darkMode');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const useDark = storedTheme ? (storedTheme === 'dark' || storedTheme === 'true') : prefersDark;
            document.documentElement.classList.toggle('dark', useDark);
        })();
        window.tailwind = window.tailwind || {};
        window.tailwind.config = { darkMode: 'class' };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
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
<body class="h-full bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-300">
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

