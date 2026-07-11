<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

$urls = [
    ['loc' => APP_URL . '/', 'changefreq' => 'weekly', 'priority' => '1.0'],
    ['loc' => APP_URL . '/menu.php', 'changefreq' => 'daily', 'priority' => '0.9'],
    ['loc' => APP_URL . '/rewards.php', 'changefreq' => 'weekly', 'priority' => '0.7'],
    ['loc' => APP_URL . '/user_guide.php', 'changefreq' => 'monthly', 'priority' => '0.5'],
];

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET),
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $products = $pdo->query('SELECT id FROM products WHERE is_available = 1 ORDER BY id')->fetchAll();
    foreach ($products as $product) {
        $urls[] = ['loc' => APP_URL . '/product_details.php?id=' . (int)$product['id'], 'changefreq' => 'weekly', 'priority' => '0.8'];
    }
} catch (Throwable $error) {
    error_log('Sitemap product query failed: ' . $error->getMessage());
}

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($urls as $url) {
    echo '  <url><loc>', htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8'), '</loc><changefreq>', $url['changefreq'], '</changefreq><priority>', $url['priority'], '</priority></url>', "\n";
}
echo '</urlset>';
