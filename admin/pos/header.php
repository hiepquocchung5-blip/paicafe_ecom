<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions_pos.php';
require_once __DIR__ . '/includes/db_connect.php';

$current_page_name = basename($_SERVER['PHP_SELF']);
$tailwind_css = load_tailwind_css([
    dirname(__DIR__) . '/assets/css/tailwind.css',
    dirname(__DIR__, 2) . '/assets/css/tailwind.css',
]);
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Paicafe POS</title>
    <style><?= $tailwind_css ?></style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script>
        function posDashboard() {
            return {
                currentTime: new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }),
                currentDate: new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' }),
                init() {
                    setInterval(() => {
                        this.currentTime = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                    }, 1000);
                }
            };
        }
    </script>
    <style>
        .dashboard-bg { background-color: #3B0764; background-image: radial-gradient(circle at 1% 1%, #DA70D6, rgba(218, 112, 214, 0) 50%), radial-gradient(circle at 99% 1%, #FFC0CB, rgba(255, 192, 203, 0) 50%), radial-gradient(circle at 50% 99%, #3B0764, rgba(59, 7, 100, 0) 50%); background-size: cover; background-attachment: fixed; }
        .glass-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.3s ease; }
        .glass-card:hover { background: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.2); transform: translateY(-5px); }
        #rotate-overlay { display: none; position: fixed; inset: 0; background-color: #1a202c; color: white; z-index: 100; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
        #rotate-overlay i { font-size: 5rem; margin-bottom: 1rem; animation: rotate-anim 2.5s ease-in-out infinite; }
        @keyframes rotate-anim { 0% { transform: rotate(0deg); } 40% { transform: rotate(90deg); } 60% { transform: rotate(90deg); } 100% { transform: rotate(0deg); } }
        @media (max-width: 768px) and (orientation: portrait) { #rotate-overlay { display: flex; } .dashboard-container, .pos-container { display: none; } }
        body { background-color: transparent; }
        .pos-scroll::-webkit-scrollbar { width: 5px; }
        .pos-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .pos-scroll::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
        @media print { body > *:not(.voucher-print-area) { display: none !important; } .no-print { display: none !important; } .voucher-print-area { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none !important; border: none !important; display: block; visibility: visible; } }
    </style>
</head>
<body class="h-full">
