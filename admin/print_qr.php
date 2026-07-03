<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$table_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$table = null;

if ($table_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM tables WHERE id = ?");
    $stmt->execute([$table_id]);
    $table = $stmt->fetch();
}

$qr_url = "https://paicafe.online/menu.php?qr_table_id_menu=" . urlencode($table['qr_code_identifier'] ?? '');
$qr_image_src = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . $qr_url;

// Google Maps Embed URL for your address
// You can get this by going to Google Maps, searching for your address, clicking "Share", then "Embed a map".
// Adjust width, height, and allowfullscreen as needed.
$map_embed_url = "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3819.382025806443!2d96.18247077490072!3d16.798157122137025!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30c1ed2b2a1a89a1%3A0x1c8b3d6a2f3e8f80!2sThanthumar%20Housing%2C%20Yangon!5e0!3m2!1sen!2smm!4v1701389088673!5m2!1sen!2smm"; // REPLACE WITH YOUR ACTUAL EMBED URL
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print QR Code for <?= e($table['table_number'] ?? 'Table') ?></title>
    <link rel="stylesheet" href="/admin/assets/css/tailwind.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        @page { size: A4; margin: 1cm; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            /* Hide the map on print if it causes issues or you don't want it printed */
            .map-container { display: none; }
        }
    </style>
</head>
<body class="bg-gray-200">
    <?php if ($table): ?>
        <div class="max-w-xl mx-auto bg-white p-6 rounded-lg text-center shadow-lg">
            <div class="border-4 border-dashed border-gray-300 p-8">
                <div class="flex flex-col items-center justify-center mb-6">
                    <img src="/assets/images/logo.jpg" alt="Paicafe Logo" class="h-20 mb-3"> <div>
                        <h2 class="text-3xl font-extrabold text-gray-900">Paicafe</h2>
                        <p class="text-md text-gray-600">Delicious moments, just a scan away!</p>
                    </div>
                </div>
                
                <h1 class="text-6xl font-bold text-orange-600 my-4"><?= e($table['table_number']) ?></h1>
                <p class="text-xl text-gray-700 mb-6 font-semibold"><?= e($table['floor']) ?></p>
                
                <img src="<?= e($qr_image_src) ?>" alt="QR Code for Table" class="w-64 h-64 mx-auto mb-8 p-3 bg-white border-4 border-orange-200 rounded-lg shadow-xl">

                <p class="text-xl font-bold text-gray-800 mb-2">Scan this QR code!</p>
                <p class="text-lg text-gray-700 leading-tight">Effortlessly browse our menu and order your favorites directly from your seat.</p>
                
                <div class="mt-10 border-t-2 border-dashed border-gray-400 pt-6 text-sm text-gray-700">
                    <p class="font-bold text-base mb-2">Find Us Here:</p>
                    <p>No 11, Thanthumar Housing, Thanthumar Rd</p>
                    <p>Thingangyun Township, Thuwanna, Yangon</p>
                    <p class="font-bold mt-2">Phone: 09890907724</p>
                </div>

                <div class="map-container mt-8 w-full">
                    <h3 class="font-bold text-lg mb-3">Our Location:</h3>
                    <iframe 
                        src="<?= e($map_embed_url) ?>" 
                        width="100%" 
                        height="250" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade"
                        class="rounded-lg shadow-md"
                    ></iframe>
                </div>

                <div class="mt-10 pt-6 border-t-2 border-dashed border-gray-400">
                    <p class="text-xl font-bold text-orange-700 mb-3">Loved Your Meal?</p>
                    <p class="text-lg text-gray-800">Please take a moment to leave a review and tell us what you enjoyed most about our delicious food!</p>
                    <p class="text-base text-gray-600 mt-2">Your feedback helps us make Paicafe even better for you!</p>
                    <p class="text-base font-bold text-green-600 mt-4">Registered users earn loyalty points for every review!</p>
                </div>

            </div>
        </div>
        <div class="text-center mt-8 no-print">
            <button onclick="window.print()" class="btn-brand">Print This Table Stand</button>
        </div>
    <?php else: ?>
        <p class="text-center font-bold text-red-500 text-2xl mt-12">Table not found. Please ensure a valid table ID is provided.</p>
    <?php endif; ?>
</body>
</html>
