<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$tailwind_css_candidates = [
    __DIR__ . '/assets/css/tailwind.css',
    dirname(__DIR__) . '/assets/css/tailwind.css',
];
$tailwind_css = '';
foreach ($tailwind_css_candidates as $tailwind_css_path) {
    if (is_readable($tailwind_css_path)) {
        $tailwind_css = file_get_contents($tailwind_css_path);
        break;
    }
}

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
    <style><?= $tailwind_css ?></style>
    <style>
        @page { size: A4; margin: 1cm; }
        body {
            background:
                radial-gradient(circle at 20% 10%, rgba(20, 184, 166, 0.16), transparent 26%),
                radial-gradient(circle at 80% 0%, rgba(124, 58, 237, 0.12), transparent 24%),
                #eef6f4;
            color: #17313a;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .qr-sheet {
            background: rgba(255, 255, 255, 0.76);
            border: 1px solid rgba(255, 255, 255, 0.62);
            box-shadow: 0 24px 70px rgba(19, 49, 58, 0.16), inset 0 1px 0 rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(22px) saturate(1.28);
            -webkit-backdrop-filter: blur(22px) saturate(1.28);
        }
        .qr-frame {
            background:
                linear-gradient(135deg, rgba(20, 184, 166, 0.08), transparent 32%),
                linear-gradient(315deg, rgba(217, 119, 6, 0.1), transparent 34%),
                rgba(255, 255, 255, 0.68);
            border: 2px dashed rgba(15, 118, 110, 0.34);
        }
        .btn-print {
            background: linear-gradient(135deg, #0f766e, #7c3aed);
            color: white;
            border-radius: 16px;
            padding: 14px 24px;
            font-weight: 900;
            font-size: 12px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            box-shadow: 0 18px 36px rgba(15, 118, 110, 0.2);
        }
        @media print {
            body {
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print { display: none !important; }
            /* Hide the map on print if it causes issues or you don't want it printed */
            .map-container { display: none; }
            .qr-sheet {
                box-shadow: none !important;
                border: none !important;
                backdrop-filter: none !important;
                background: #fff !important;
            }
        }
    </style>
</head>
<body class="min-h-screen px-4 py-8">
    <?php if ($table): ?>
        <div class="qr-sheet max-w-xl mx-auto p-6 sm:p-8 rounded-[2rem] text-center">
            <div class="qr-frame rounded-[1.5rem] p-8">
                <div class="flex flex-col items-center justify-center mb-6">
                    <img src="/assets/images/logo.jpg" alt="Paicafe Logo" class="h-20 mb-3 rounded-2xl shadow-sm">
                    <div>
                        <h2 class="text-3xl font-black text-slate-900 tracking-tight">Paicafe</h2>
                        <p class="text-sm text-slate-500 font-bold uppercase tracking-[0.18em] mt-1">Scan, order, relax</p>
                    </div>
                </div>
                
                <div class="inline-flex items-center gap-3 rounded-full bg-teal-500/10 border border-teal-500/20 px-5 py-2 mb-5">
                    <span class="h-2.5 w-2.5 rounded-full bg-teal-500"></span>
                    <span class="text-xs font-black uppercase tracking-[0.2em] text-teal-700">Table Stand</span>
                </div>

                <h1 class="text-6xl font-black text-orange-600 my-3 tracking-tight"><?= e($table['table_number']) ?></h1>
                <p class="text-xl text-slate-700 mb-6 font-bold"><?= e($table['floor']) ?></p>
                
                <img src="<?= e($qr_image_src) ?>" alt="QR Code for Table" class="w-64 h-64 mx-auto mb-8 p-3 bg-white border-4 border-orange-200 rounded-3xl shadow-xl">

                <p class="text-xl font-black text-slate-900 mb-2">Scan this QR code</p>
                <p class="text-base text-slate-600 leading-relaxed max-w-sm mx-auto">Browse the menu and order your favorites directly from your seat.</p>
                
                <div class="mt-10 border-t-2 border-dashed border-slate-300 pt-6 text-sm text-slate-700">
                    <p class="font-black text-base mb-2 text-slate-900">Find Us Here</p>
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

                <div class="mt-10 pt-6 border-t-2 border-dashed border-slate-300">
                    <p class="text-xl font-black text-orange-700 mb-3">Loved Your Meal?</p>
                    <p class="text-base text-slate-700 leading-relaxed">Please leave a review and tell us what you enjoyed most.</p>
                    <p class="text-sm text-slate-500 mt-2">Your feedback helps us make Paicafe better.</p>
                    <p class="text-sm font-black text-green-600 mt-4">Registered users earn loyalty points for every review.</p>
                </div>

            </div>
        </div>
        <div class="text-center mt-8 no-print">
            <button onclick="window.print()" class="btn-print">Print Table Stand</button>
        </div>
    <?php else: ?>
        <p class="text-center font-bold text-red-500 text-2xl mt-12">Table not found. Please ensure a valid table ID is provided.</p>
    <?php endif; ?>
</body>
</html>
