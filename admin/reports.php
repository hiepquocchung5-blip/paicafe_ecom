<?php
require_once __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/partials/header.php';

// Set default date range to the current month
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// --- Fetch Financial Data ---
// 1. Calculate Total Revenue from completed orders
$revenue_stmt = $pdo->prepare("
    SELECT SUM(final_amount) as total_revenue 
    FROM orders 
    WHERE status = 'completed' AND DATE(created_at) BETWEEN ? AND ?
");
$revenue_stmt->execute([$start_date, $end_date]);
$total_revenue = $revenue_stmt->fetchColumn() ?? 0;

// 2. Calculate Total Expenses
$expenses_stmt = $pdo->prepare("
    SELECT SUM(amount) as total_expenses 
    FROM expenses 
    WHERE expense_date BETWEEN ? AND ?
");
$expenses_stmt->execute([$start_date, $end_date]);
$total_expenses = $expenses_stmt->fetchColumn() ?? 0;

// 3. Calculate Net Profit
$net_profit = $total_revenue - $total_expenses;

// Fetch top 5 selling products for the period
$top_products_stmt = $pdo->prepare("
    SELECT p.name_en, SUM(oi.quantity) as total_sold
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed' AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY p.name_en
    ORDER BY total_sold DESC
    LIMIT 5
");
$top_products_stmt->execute([$start_date, $end_date]);
$top_products = $top_products_stmt->fetchAll();

$profitability_stmt = $pdo->prepare("
    SELECT 
        p.name_en,
        SUM(oi.quantity) as total_sold,
        p.price as sale_price,
        -- Calculate the cost of a single product based on its recipe
        (SELECT SUM(r.quantity_used * i.cost) FROM recipes r JOIN inventory_items i ON r.inventory_item_id = i.id WHERE r.product_id = p.id) as cost_per_item
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed' AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY p.id, p.name_en, p.price
    ORDER BY total_sold DESC
");
$profitability_stmt->execute([$start_date, $end_date]);
$product_profitability = $profitability_stmt->fetchAll();   
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-emerald-500 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">Financial Intelligence</h2>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-800 dark:text-white tracking-tight leading-none">Financial Reports</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2"><?= e(date('d M Y', strtotime($start_date))) ?> to <?= e(date('d M Y', strtotime($end_date))) ?></p>
        </div>
    </div>

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 lg:p-8 shadow-2xl mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-5 items-end">
            <div>
                <label for="start_date" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Start Date</label>
                <input type="date" name="start_date" id="start_date" value="<?= e($start_date) ?>" class="form-input">
            </div>
            <div>
                <label for="end_date" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">End Date</label>
                <input type="date" name="end_date" id="end_date" value="<?= e($end_date) ?>" class="form-input">
            </div>
            <button type="submit" class="btn-brand">Generate Report</button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="liquid-surface rounded-[2rem] border p-6">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Revenue</p>
            <p class="text-3xl font-black text-emerald-500 mt-2"><?= number_format($total_revenue, 2) ?> Ks</p>
        </div>
        <div class="liquid-surface rounded-[2rem] border p-6">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Expenses</p>
            <p class="text-3xl font-black text-red-500 mt-2"><?= number_format($total_expenses, 2) ?> Ks</p>
        </div>
        <div class="liquid-surface rounded-[2rem] border p-6">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Net Profit</p>
            <p class="text-3xl font-black mt-2 <?= $net_profit >= 0 ? 'text-blue-500' : 'text-red-500' ?>"><?= number_format($net_profit, 2) ?> Ks</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
        <div class="lg:col-span-2 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 shadow-2xl">
            <h2 class="text-xl font-black text-slate-800 dark:text-white mb-5">Top Selling Products</h2>
            <div class="space-y-3">
                <?php foreach($top_products as $product): ?>
                <div class="flex justify-between items-center p-3 rounded-2xl bg-white/40 dark:bg-slate-950/30 border border-slate-200 dark:border-slate-800">
                    <span class="font-bold text-slate-800 dark:text-white"><?= e($product['name_en']) ?></span>
                    <span class="font-black bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-3 py-1 rounded-full text-xs"><?= e($product['total_sold']) ?> sold</span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($top_products)): ?>
                    <div class="p-8 text-center text-slate-500">No completed sales in this period.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="lg:col-span-3 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl">
            <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800">
                <h2 class="text-xl font-black text-slate-800 dark:text-white">Product Profitability</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr>
                            <th class="p-4">Product</th>
                            <th class="p-4 text-center">Sold</th>
                            <th class="p-4 text-right">Sale</th>
                            <th class="p-4 text-right">COGS</th>
                            <th class="p-4 text-right">Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($product_profitability as $item): 
                            $net_profit_per_item = (float)$item['sale_price'] - (float)($item['cost_per_item'] ?? 0);
                            $total_profit = $net_profit_per_item * (float)$item['total_sold'];
                        ?>
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="p-4 font-black text-slate-800 dark:text-white"><?= e($item['name_en']) ?></td>
                            <td class="p-4 text-center font-bold"><?= e($item['total_sold']) ?></td>
                            <td class="p-4 text-right text-emerald-500 font-bold"><?= number_format($item['sale_price'], 2) ?> Ks</td>
                            <td class="p-4 text-right text-red-500 font-bold"><?= number_format($item['cost_per_item'] ?? 0, 2) ?> Ks</td>
                            <td class="p-4 text-right font-black text-blue-500"><?= number_format($total_profit, 2) ?> Ks</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($product_profitability)): ?>
                        <tr><td colspan="5" class="p-10 text-center text-slate-500">No product profitability data for this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<?php include 'partials/footer.php'; ?>
