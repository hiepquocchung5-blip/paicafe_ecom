<div class="px-8 py-6 border-b border-gray-700">
    <h1 class="text-2xl font-bold">Paicafe Admin</h1>
</div>
<nav class="flex-grow overflow-y-auto">
    <?php if (has_permission('view_dashboard')): ?>
        <a href="/index.php" class="flex items-center px-8 py-3 hover:bg-gray-700">
            <i class="fas fa-tachometer-alt fa-fw mr-3"></i> Dashboard
        </a>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'developer'): ?>
        <a href="/developer_dashboard.php" class="flex items-center px-8 py-3 text-yellow-400 hover:bg-gray-700">
            <i class="fas fa-code fa-fw mr-3"></i> Developer
        </a>
    <?php endif; ?>

    <?php if (has_permission('manage_orders')): ?>
        <a href="/orders.php" class="flex items-center px-8 py-3 hover:bg-gray-700">
            <i class="fas fa-receipt fa-fw mr-3"></i> Orders
        </a>
    <?php endif; ?>

    <?php if (has_permission('use_pos')): ?>
        <a href="/pos.php" class="flex items-center px-8 py-3 text-green-400 hover:bg-gray-700">
            <i class="fas fa-cash-register fa-fw mr-3"></i> POS System
        </a>
    <?php endif; ?>

    <?php if (has_permission('manage_reservations')): ?>
        <a href="/reservations.php" class="flex items-center px-8 py-3 hover:bg-gray-700">
            <i class="fas fa-calendar-check fa-fw mr-3"></i> Reservations
        </a>
    <?php endif; ?>
    
    <?php if (has_permission('manage_products') || has_permission('manage_recipes') || has_permission('manage_inventory')): ?>
    <div class="mt-4 pt-4 border-t border-gray-700">
        <h2 class="px-8 mb-2 text-xs uppercase text-gray-400 tracking-wider">Manage</h2>
        <?php if (has_permission('manage_products')): ?>
            <a href="/combos.php" class="flex items-center px-8 py-3 hover:bg-gray-700">
        <i class="fas fa-layer-group fa-fw mr-3"></i> Combos
            </a>
            <a href="/coupons.php" class="flex items-center px-8 py-3 hover:bg-gray-700">
                <i class="fas fa-ticket-alt fa-fw mr-3"></i> Coupons
            </a>
            <a href="/products.php" class="flex items-center px-8 py-3 hover:bg-gray-700"><i class="fas fa-box fa-fw mr-3"></i> Products</a>
            <a href="/categories.php" class="flex items-center px-8 py-3 hover:bg-gray-700"><i class="fas fa-tags fa-fw mr-3"></i> Categories</a>
        <?php endif; ?>
        <?php if (has_permission('manage_recipes')): ?>
            <a href="/recipes.php" class="flex items-center px-8 py-3 hover:bg-gray-700"><i class="fas fa-blender-phone fa-fw mr-3"></i> Recipes</a>
        <?php endif; ?>
        <?php if (has_permission('manage_inventory')): ?>
            <a href="/inventory.php" class="flex items-center px-8 py-3 hover:bg-gray-700"><i class="fas fa-boxes-stacked fa-fw mr-3"></i> Inventory</a>
            <a href="/daily_use_stock.php" class="flex items-center px-8 py-3 hover:bg-gray-700"><i class="fas fa-dolly fa-fw mr-3"></i> Stock Usage</a>
            <a href="/inventory_logs.php" class="flex items-center px-8 py-3 hover:bg-gray-700"><i class="fas fa-history fa-fw mr-3"></i> Inventory Logs</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (has_permission('view_reports') || has_permission('manage_admins') || has_permission('manage_settings')): ?>
    <div class="mt-4 pt-4 border-t border-gray-700">
        <h2 class="px-8 mb-2 text-xs uppercase text-gray-400 tracking-wider">System</h2>
        <?php if (has_permission('view_reports')): ?>
            <a href="/reports.php" class="flex items-center px-8 py-3 hover:bg-gray-700"><i class="fas fa-chart-line fa-fw mr-3"></i> Repor
            </a>
            <a href="/sales_dashboard.php" class="flex items-center px-8 py-3 hover:bg-gray-700">
                <i class="fas fa-chart-bar fa-fw mr-3"></i> Sales Dashboard
             </a>
        <?php endif; ?>
        <?php if (has_permission('manage_expenses')): ?>
            <a href="/expenses.php" class="flex items-center px-8 py-3 hover:bg-gray-700"><i class="fas fa-money-bill-wave fa-fw mr-3"></i> Expenses</a>
        <?php endif; ?>
        <?php if (has_permission('manage_admins')): ?>
            <a href="/admins.php" class="flex items-center px-8 py-3 hover:bg-gray-700"><i class="fas fa-user-shield fa-fw mr-3"></i> Admins</a>
        <?php endif; ?>
        <?php if (has_permission('manage_tables')): ?>
            <a href="/user_properties.php" class="flex items-center px-8 py-3 hover:bg-gray-700">
                <i class="fas fa-users-cog fa-fw mr-3"></i> User Properties
            </a>
            <a href="/tables.php" class="flex items-center px-8 py-3 hover:bg-gray-700"><i class="fas fa-chair fa-fw mr-3"></i> Tables</a>
            <a href="/floor_plan.php" class="flex items-center px-8 py-3 hover:bg-gray-700"><i class="fas fa-map-marked-alt fa-fw mr-3"></i> Floor Plan</a>
        <?php endif; ?>
        <?php if (has_permission('manage_rewards')): ?>
            <a href="/rewards.php" class="flex items-center px-8 py-3 hover:bg-gray-700"><i class="fas fa-gift fa-fw mr-3"></i> Rewards</a>
            <a href="/redemptions.php" class="flex items-center px-8 py-3 hover:bg-gray-700"><i class="fas fa-ticket-alt fa-fw mr-3"></i> Redemptions</a>
        <?php endif; ?>
        <?php if (has_permission('manage_settings')): ?>
            <a href="/settings.php" class="flex items-center px-8 py-3 hover:bg-gray-700"><i class="fas fa-cog fa-fw mr-3"></i> Settings</a>
        <?php endif; ?>
        <?php if (has_permission('manage_permissions')): ?>
             <a href="/permissions.php" class="flex items-center px-8 py-3 hover:bg-gray-700"><i class="fas fa-lock fa-fw mr-3"></i> Permissions</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</nav>
<div class="px-8 py-4 border-t border-gray-700">
    <a href="/logout.php" class="flex items-center text-red-400 hover:text-red-300">
        <i class="fas fa-sign-out-alt fa-fw mr-3"></i> Logout
    </a>
</div>