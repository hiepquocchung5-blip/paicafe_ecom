<div class="px-8 py-8 border-b border-gray-700/50 bg-gray-900/50">
    <div class="flex items-center space-x-3">
        <div class="w-10 h-10 bg-orange-600 rounded-xl flex items-center justify-center shadow-lg shadow-orange-600/20">
            <i class="fas fa-coffee text-white text-xl"></i>
        </div>
        <div>
            <h1 class="text-xl font-black tracking-tighter text-white">PAICAFE</h1>
            <p class="text-[10px] text-orange-500 font-bold tracking-[0.2em] uppercase">Control Center</p>
        </div>
    </div>
</div>
<nav class="flex-grow overflow-y-auto py-6 space-y-1 custom-scrollbar">
    <?php if (has_permission('view_dashboard')): ?>
        <a href="index.php" class="group flex items-center px-8 py-3 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'index.php') !== false ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-gray-400' ?>">
            <i class="fas fa-tachometer-alt fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Dashboard
        </a>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'developer'): ?>
        <a href="developer_dashboard.php" class="group flex items-center px-8 py-3 text-sm font-medium text-yellow-500 transition-all duration-200 hover:bg-yellow-500/10 <?= strpos($_SERVER['PHP_SELF'], 'developer_dashboard.php') !== false ? 'bg-yellow-500 text-black' : '' ?>">
            <i class="fas fa-code fa-fw mr-3 group-hover:rotate-12 transition-transform"></i> Developer
        </a>
    <?php endif; ?>

    <?php if (has_permission('manage_orders')): ?>
        <a href="orders.php" class="group flex items-center px-8 py-3 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'orders.php') !== false ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-gray-400' ?>">
            <i class="fas fa-receipt fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Orders
        </a>
    <?php endif; ?>

    <?php if (has_permission('use_pos')): ?>
        <a href="pos.php" class="group flex items-center px-8 py-3 text-sm font-medium text-green-400 transition-all duration-200 hover:bg-green-600/10 <?= strpos($_SERVER['PHP_SELF'], 'pos.php') !== false ? 'bg-green-600 text-white shadow-lg shadow-green-600/20' : '' ?>">
            <i class="fas fa-cash-register fa-fw mr-3 group-hover:-translate-y-0.5 transition-transform"></i> POS System
        </a>
    <?php endif; ?>

    <?php if (has_permission('manage_reservations')): ?>
        <a href="reservations.php" class="group flex items-center px-8 py-3 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'reservations.php') !== false ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-gray-400' ?>">
            <i class="fas fa-calendar-check fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Reservations
        </a>
    <?php endif; ?>
    
    <?php if (has_permission('manage_products') || has_permission('manage_recipes') || has_permission('manage_inventory')): ?>
    <div class="mt-8 pt-6 border-t border-gray-700/30">
        <h2 class="px-8 mb-4 text-[10px] font-black uppercase text-gray-500 tracking-[0.2em]">Inventory Engine</h2>
        <?php if (has_permission('manage_products')): ?>
            <a href="combos.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'combos.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-layer-group fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Combos
            </a>
            <a href="coupons.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'coupons.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-ticket-alt fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Coupons
            </a>
            <a href="products.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'products.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-box fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Products
            </a>
            <a href="categories.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'categories.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-tags fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Categories
            </a>
            <a href="reviews.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'reviews.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-star fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Customer Reviews
            </a>
            <a href="prompt_generator.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'prompt_generator.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-robot fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Prompt Engine
            </a>
        <?php endif; ?>
        <?php if (has_permission('manage_recipes')): ?>
            <a href="recipes.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'recipes.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-mortar-pestle fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Recipes
            </a>
        <?php endif; ?>
        <?php if (has_permission('manage_inventory')): ?>
            <a href="inventory.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'inventory.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-boxes-stacked fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Inventory
            </a>
            <a href="daily_use_stock.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'daily_use_stock.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-clipboard-list fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Stock Usage
            </a>
            <a href="inventory_logs.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'inventory_logs.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-history fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Inventory Logs
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (has_permission('view_reports') || has_permission('manage_admins') || has_permission('manage_settings')): ?>
    <div class="mt-8 pt-6 border-t border-gray-700/30">
        <h2 class="px-8 mb-4 text-[10px] font-black uppercase text-gray-500 tracking-[0.2em]">Management Portal</h2>
        <?php if (has_permission('view_reports')): ?>
            <a href="reports.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'reports.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-chart-line fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Financial Reports
            </a>
            <a href="sales_dashboard.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'sales_dashboard.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-chart-bar fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Sales Dashboard
             </a>
        <?php endif; ?>
        <?php if (has_permission('manage_expenses')): ?>
            <a href="expenses.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'expenses.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-money-bill-wave fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Expenses
            </a>
        <?php endif; ?>
        <?php if (has_permission('manage_admins')): ?>
            <a href="admins.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'admins.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-user-shield fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Team Management
            </a>
        <?php endif; ?>
        <?php if (has_permission('manage_tables')): ?>
            <a href="user_properties.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'user_properties.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-users-cog fa-fw mr-3 group-hover:scale-110 transition-transform"></i> User Analytics
            </a>
            <a href="tables.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'tables.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-table fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Table Setup
            </a>
            <a href="floor_plan.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'floor_plan.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-map-location-dot fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Floor Plan
            </a>
        <?php endif; ?>
        <?php if (has_permission('manage_rewards')): ?>
            <a href="rewards.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'rewards.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-trophy fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Rewards
            </a>
            <a href="redemptions.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'redemptions.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-ticket-alt fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Redemptions
            </a>
        <?php endif; ?>
        <?php if (has_permission('manage_settings')): ?>
            <a href="settings.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'settings.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-sliders fa-fw mr-3 group-hover:scale-110 transition-transform"></i> System Settings
            </a>
        <?php endif; ?>
        <?php if (has_permission('manage_permissions')): ?>
             <a href="permissions.php" class="group flex items-center px-8 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-orange-600/10 hover:text-orange-500 <?= strpos($_SERVER['PHP_SELF'], 'permissions.php') !== false ? 'text-orange-500' : 'text-gray-400' ?>">
                <i class="fas fa-shield-halved fa-fw mr-3 group-hover:scale-110 transition-transform"></i> Security Matrix
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</nav>
<div class="px-8 py-6 border-t border-gray-700/30 bg-gray-900/50">
    <a href="logout.php" class="group flex items-center text-sm font-bold text-red-500 hover:text-red-400 transition-colors">
        <i class="fas fa-power-off fa-fw mr-3 group-hover:rotate-90 transition-transform duration-300"></i> Disconnect
    </a>
</div>