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

<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">Financial Reports</h1>

    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <form method="GET" class="flex items-end space-x-4">
            <div>
                <label for="start_date" class="block text-gray-700">Start Date</label>
                <input type="date" name="start_date" id="start_date" value="<?= e($start_date) ?>" class="form-input">
            </div>
            <div>
                <label for="end_date" class="block text-gray-700">End Date</label>
                <input type="date" name="end_date" id="end_date" value="<?= e($end_date) ?>" class="form-input">
            </div>
            <button type="submit" class="btn-brand">Generate Report</button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-green-100 p-6 rounded-lg shadow-md text-center">
            <h2 class="text-green-800 font-semibold">Total Revenue</h2>
            <p class="text-3xl font-bold text-green-600 mt-2"><?= number_format($total_revenue, 2) ?> Ks</p>
        </div>
        <div class="bg-red-100 p-6 rounded-lg shadow-md text-center">
            <h2 class="text-red-800 font-semibold">Total Expenses</h2>
            <p class="text-3xl font-bold text-red-600 mt-2"><?= number_format($total_expenses, 2) ?> Ks</p>
        </div>
        <div class="bg-blue-100 p-6 rounded-lg shadow-md text-center">
            <h2 class="text-blue-800 font-semibold">Net Profit</h2>
            <p class="text-3xl font-bold mt-2 <?= $net_profit >= 0 ? 'text-blue-600' : 'text-red-600' ?>">
                <?= number_format($net_profit, 2) ?> Ks
            </p>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4">Top Selling Products (<?= e(date('d M', strtotime($start_date))) ?> - <?= e(date('d M', strtotime($end_date))) ?>)</h2>
        <ul class="space-y-2">
            <?php foreach($top_products as $product): ?>
            <li class="flex justify-between items-center p-2 border-b">
                <span class="font-medium"><?= e($product['name_en']) ?></span>
                <span class="font-bold bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-sm"><?= e($product['total_sold']) ?> sold</span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">Financial Reports</h1>
    <div class="bg-white p-6 rounded-lg shadow-md mt-8">
        <h2 class="text-2xl font-bold mb-4">Product Profitability (<?= e(date('d M', strtotime($start_date))) ?> - <?= e(date('d M', strtotime($end_date))) ?>)</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-3">Product</th>
                        <th class="p-3 text-center">Units Sold</th>
                        <th class="p-3 text-right">Sale Price</th>
                        <th class="p-3 text-right">Cost Per Item (COGS)</th>
                        <th class="p-3 text-right">Net Profit Per Item</th>
                        <th class="p-3 text-right">Total Profit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($product_profitability as $item): 
                        $net_profit_per_item = $item['sale_price'] - $item['cost_per_item'];
                        $total_profit = $net_profit_per_item * $item['total_sold'];
                    ?>
                    <tr class="border-b">
                        <td class="p-3 font-medium"><?= e($item['name_en']) ?></td>
                        <td class="p-3 text-center"><?= e($item['total_sold']) ?></td>
                        <td class="p-3 text-right text-green-600"><?= number_format($item['sale_price'], 2) ?> Ks</td>
    
                        <td class="p-3 text-right text-red-600"><?= number_format($item['cost_per_item'] ?? 0, 2) ?> Ks</td>
    
                        <td class="p-3 text-right font-bold text-blue-600"><?= number_format($net_profit_per_item, 2) ?> Ks</td>
                        <td class="p-3 text-right font-bold text-blue-800"><?= number_format($total_profit, 2) ?> Ks</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<?php include 'partials/footer.php'; ?>