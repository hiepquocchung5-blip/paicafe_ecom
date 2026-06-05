<?php
// We must go UP TWO directories from /admin/partials/ to get to the root
// and then go into /includes/
require_once dirname(__DIR__, 2) . '/includes/db_connect.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// This function is now loaded from the master functions.php file
require_admin_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paicafe - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-100">
<div class="flex h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    
    <!-- Desktop Sidebar -->
    <div class="hidden lg:flex lg:flex-shrink-0">
        <div class="flex flex-col w-64 bg-gray-800 text-white">
            <?php include 'sidebar_content.php'; // Reusable sidebar content ?>
        </div>
    </div>

    <!-- Mobile Sidebar -->
    <div x-show="sidebarOpen" class="fixed inset-0 flex z-40 lg:hidden" @click.away="sidebarOpen = false">
        <div class="fixed inset-0 bg-black opacity-50"></div>
        <div class="relative flex-1 flex flex-col max-w-xs w-full bg-gray-800 text-white">
            <?php include 'sidebar_content.php'; // Re-use the same sidebar content ?>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white shadow-md p-4 flex justify-between items-center">
            <button @click.stop="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500 focus:outline-none">
                <i class="fas fa-bars fa-lg"></i>
            </button>
            <div class="flex items-center space-x-2">
                <h2 class="text-xl font-semibold">Welcome, <?= e($_SESSION['admin_username']) ?>!</h2>
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-800">
                    <?= e(ucfirst($_SESSION['user_type'])) ?>
                </span>
            </div>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">

