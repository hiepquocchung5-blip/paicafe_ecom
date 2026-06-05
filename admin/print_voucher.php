<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order_details = null;
$order_items = [];
$table_number = null;

if ($order_id > 0) {
    // Fetch order details
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order_details = $stmt->fetch();

    // Fetch order items
    if ($order_details) {
        $items_stmt = $pdo->prepare("SELECT oi.quantity, p.name_en, oi.price_per_item FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $items_stmt->execute([$order_id]);
        $order_items = $items_stmt->fetchAll();
        
        if ($order_details['table_id']) {
            $table_stmt = $pdo->prepare("SELECT table_number FROM tables WHERE id = ?");
            $table_stmt->execute([$order_details['table_id']]);
            $table_number = $table_stmt->fetchColumn();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Receipt #<?= e($order_id) ?></title>
    <style>
        /* Basic styles for the receipt content */
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
        }
        .receipt-container {
            width: 78mm; /* Target width for the printer */
            padding: 6mm;
            box-sizing: border-box;
        }
        .logo {
            max-width: 150px; /* Adjust logo size as needed */
            margin: 0 auto 10px;
            display: block;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .title { font-size: 16px; margin-bottom: 5px; }
        .address { font-size: 11px; line-height: 1.4; }
        .separator { border-top: 1px dashed #000; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; }
        .right { text-align: right; }

        /* --- THIS IS THE KEY PART FOR PRINTING --- */
        @media print {
            /* Tell the browser the page size is for a receipt */
            @page {
                size: 80mm 3276mm; /* Width x very long Height */
                margin: 0;
            }
            body {
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body class="bg-gray-200">
    <?php if ($order_details && !empty($order_items)): ?>
        <div class="receipt-container">
            <div class="center">
                <img src="/assets/images/logo.jpg" alt="Logo" class="logo">
                
                <h1 class="title bold">Pai cafe & Lounge</h1>
                
                <div class="address">
                    <p>No 11, Thanthumar Housing, Thanthumar Rd</p>
                    <p>Thingangyun Township, Thuwanna, Yangon</p>
                    <p>Phone: 09890907724</p>
                </div>
                
                <div class="separator"></div>
                
                <p>Order #<?= e($order_details['id']) ?></p>
                <?php if ($table_number): ?>
                    <p class="bold">Table: <?= e($table_number) ?></p>
                <?php endif; ?>
                <p><?= date('d M Y, h:i A', strtotime($order_details['created_at'])) ?></p>
            </div>
            <div class="separator"></div>
            <table>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td><?= e($item['quantity']) ?>x <?= e($item['name_en']) ?></td>
                        <td class="right"><?= number_format($item['price_per_item'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="separator"></div>
            <table>
                <tbody>
                    <tr>
                        <td>Subtotal:</td>
                        <td class="right"><?= number_format($order_details['total_amount'], 2) ?> Ks</td>
                    </tr>
                    <tr>
                        <td>Tax:</td>
                        <td class="right"><?= number_format($order_details['tax_amount'], 2) ?> Ks</td>
                    </tr>
                    <tr class="bold">
                        <td>Total:</td>
                        <td class="right"><?= number_format($order_details['final_amount'], 2) ?> Ks</td>
                    </tr>
                </tbody>
            </table>
            <div class="separator"></div>
            <p class="center">Thank you for your purchase!</p>
        </div>
        <script>
            window.onload = function() { window.print(); };
        </script>
    <?php else: ?>
        <p>Order not found or has no items.</p>
    <?php endif; ?>
</body>
</html>