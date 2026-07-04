<?php
/**
 * Main Functions File for Paicafe Application
 * This is the SINGLE source of truth for all functions (public and admin).
 */

// 1. SESSION MANAGEMENT
function paicafe_is_https() {
    $forwarded_proto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $forwarded_proto = trim(explode(',', $forwarded_proto)[0] ?? '');
    $cf_visitor = (string)($_SERVER['HTTP_CF_VISITOR'] ?? '');

    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $forwarded_proto === 'https'
        || (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on')
        || (($_SERVER['HTTP_FRONT_END_HTTPS'] ?? '') === 'on')
        || (strpos($cf_visitor, '"scheme":"https"') !== false)
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

function paicafe_session_cookie_options($lifetime = 0, $path = '/') {
    return [
        'lifetime' => $lifetime,
        'path' => $path,
        'domain' => '',
        'secure' => paicafe_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function paicafe_start_session() {
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (paicafe_is_https()) {
        ini_set('session.cookie_secure', '1');
    }

    session_name('PAICAFESESSID');
    session_set_cookie_params(paicafe_session_cookie_options());
    session_start();
}

function paicafe_regenerate_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function paicafe_set_cookie($name, $value, $expires, $path = '/') {
    setcookie($name, $value, [
        'expires' => $expires,
        'path' => $path,
        'domain' => '',
        'secure' => paicafe_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function paicafe_clear_cookie($name, $path = '/') {
    paicafe_set_cookie($name, '', time() - 3600, $path);
}

function paicafe_clear_admin_session() {
    unset(
        $_SESSION['admin_id'],
        $_SESSION['admin_username'],
        $_SESSION['user_type'],
        $_SESSION['permissions']
    );
}

function paicafe_destroy_session() {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool)($params['secure'] ?? paicafe_is_https()),
            'httponly' => true,
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function paicafe_login_admin(array $admin, array $permissions = []) {
    paicafe_regenerate_session();
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['user_type'] = $admin['user_type'];
    $_SESSION['permissions'] = $permissions;
    $_SESSION['admin_last_seen'] = time();
}

function paicafe_login_user(array $user) {
    paicafe_regenerate_session();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_last_seen'] = time();
}

// Ensures the session is always started with consistent cookie settings.
paicafe_start_session();

// 2. LANGUAGE SWITCHING
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = ($_GET['lang'] === 'mm') ? 'mm' : 'en';
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redirect_url);
    exit();
}


// 3. CORE HELPER FUNCTIONS

/**
 * Gets a translated string from the language files.
 */
function lang($key) {
    $lang_code = $_SESSION['lang'] ?? 'en'; 
    $lang_file = __DIR__ . '/lang/' . $lang_code . '.php';

    static $translations = [];
    if (empty($translations[$lang_code])) {
        if (file_exists($lang_file)) {
            $translations[$lang_code] = include $lang_file;
        } else {
            $translations[$lang_code] = [];
        }
    }
    return $translations[$lang_code][$key] ?? $key;
}

/**
 * A shortcut function to securely escape HTML output.
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf_token($token) {
    return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function require_csrf_token($token = null) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $token = $token ?? ($_POST['csrf_token'] ?? '');

    if (!verify_csrf_token($token)) {
        paicafe_csrf_failure_response();
    }
}

function paicafe_csrf_failure_response() {
    http_response_code(403);

    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    if (strpos($accept, 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'code' => 'csrf_token_invalid',
            'message' => 'Your security session changed. Refresh the page and try again.',
        ]);
        exit();
    }

    $back_url = $_SERVER['HTTP_REFERER'] ?? ($_SERVER['REQUEST_URI'] ?? '/admin/');
    $back_url = htmlspecialchars($back_url, ENT_QUOTES, 'UTF-8');

    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Security Check</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#edf7f4;color:#14323a;font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.card{width:min(420px,calc(100vw - 32px));padding:28px;border-radius:22px;background:rgba(255,255,255,.78);border:1px solid rgba(21,94,117,.18);box-shadow:0 24px 70px rgba(15,76,91,.16);text-align:center}.icon{width:56px;height:56px;margin:0 auto 16px;border-radius:18px;display:grid;place-items:center;background:rgba(249,115,22,.12);color:#ea580c;font-size:24px}h1{margin:0 0 10px;font-size:22px;line-height:1.15}p{margin:0 0 20px;color:#64748b;line-height:1.5}.actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}a,button{appearance:none;border:0;border-radius:14px;padding:12px 16px;font-weight:800;font-size:12px;text-transform:uppercase;letter-spacing:.08em;text-decoration:none;cursor:pointer}.primary{background:#ea580c;color:#fff}.secondary{background:#e2e8f0;color:#14323a}</style></head><body><main class="card"><div class="icon">!</div><h1>Security session changed</h1><p>Your VPN or network may have refreshed the browser session. Please reload the page, then submit again.</p><div class="actions"><button class="primary" onclick="location.reload()">Reload page</button><a class="secondary" href="' . $back_url . '">Go back</a></div></main></body></html>';
    exit();
}

function start_admin_csrf_form_injection() {
    static $started = false;

    if ($started) {
        return;
    }

    $started = true;
    $field = csrf_field();

    ob_start(static function ($html) use ($field) {
        return preg_replace_callback('/<form\b[^>]*\bmethod=["\']?post["\']?[^>]*>/i', static function ($matches) use ($field) {
            return $matches[0] . $field;
        }, $html);
    });
}

/**
 * Formats a number as currency.
 */
function format_currency($amount, $currency = 'Ks') {
    return number_format($amount, 0) . ' ' . $currency;
}

function load_first_readable_file(array $paths) {
    foreach ($paths as $path) {
        if (is_readable($path)) {
            return file_get_contents($path);
        }
    }

    return '';
}

function load_tailwind_css(array $paths = []) {
    $default_paths = [
        __DIR__ . '/../admin/assets/css/tailwind.css',
        __DIR__ . '/../assets/css/tailwind.css',
    ];

    return load_first_readable_file(array_merge($paths, $default_paths));
}

function validate_coupon($pdo, $code, $subtotal) {
    $code = strtoupper(trim((string)$code));
    $subtotal = max(0, (float)$subtotal);

    if ($code === '') {
        return [
            'status' => 'error',
            'message' => 'Please enter a coupon code.',
        ];
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM coupons
        WHERE code = ?
          AND is_active = 1
          AND (expiry_date IS NULL OR expiry_date >= CURDATE())
          AND (max_uses IS NULL OR uses_count < max_uses)
        LIMIT 1
    ");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        return [
            'status' => 'error',
            'message' => 'Invalid, expired, or fully used coupon code.',
        ];
    }

    $discount = 0;
    if ($coupon['discount_type'] === 'percentage') {
        $discount = ($subtotal * (float)$coupon['discount_value']) / 100;
    } else {
        $discount = (float)$coupon['discount_value'];
    }

    return [
        'status' => 'success',
        'discount' => min($subtotal, max(0, $discount)),
        'coupon' => $coupon,
        'message' => 'Coupon applied successfully!',
    ];
}


// 4. PUBLIC AUTHENTICATION

/**
 * Checks if a public user (customer) is logged in.
 */
function is_user_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirects to the public login page if a customer is not logged in.
 */
function require_login() {
    if (!is_user_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: /login.php');
        exit();
    }
}


// 5. ADMIN AUTHENTICATION & PERMISSIONS

/**
 * Checks if an admin-level user is logged in (any role).
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

/**
 * Redirects to the admin login page if no admin is logged in.
 * This function is used by all admin pages.
 */
function require_admin_login() {
    if (!is_admin_logged_in()) {
        header('Location: /login.php');
        exit();
    }
}

/**
 * Redirects to the kitchen login page if a 'kitchen' role user is not logged in.
 */
function require_kitchen_login() {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'kitchen') {
        header('Location: /kitchen/login.php'); 
        exit();
    }
}

/**
 * Checks if the currently logged-in admin has a specific permission.
 */
function has_permission($permission_name) {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'developer') {
        return true;
    }
    return isset($_SESSION['permissions']) && in_array($permission_name, $_SESSION['permissions']);
}


// 6. ADMIN HELPER FUNCTIONS

/**
 * Gets a specific setting from the database.
 */
function get_setting($pdo, $key, $default = null) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn() ?? $default;
}

/**
 * Logs an action performed by an admin.
 */
function log_activity($pdo, $action) {
    if (isset($_SESSION['admin_id'])) {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, action) VALUES (?, ?)");
        $stmt->execute([$_SESSION['admin_id'], $action]);
    }
}

/**
 * Calculates loyalty points to be awarded for a given amount.
 */
function calculate_points($pdo, $amount) {
    $points_rate = get_setting($pdo, 'loyalty_points_per_100_kyats', 1);
    return floor($amount / 100) * (int)$points_rate;
}

/**
 * Automatically deducts stock for all items in a completed order.
 */
function deduct_inventory_for_order($pdo, $order_id, $admin_id) {
    try {
        $order_items_stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $order_items_stmt->execute([$order_id]);
        $order_items = $order_items_stmt->fetchAll();
        $product_ids = array_column($order_items, 'product_id');
        if (empty($product_ids)) return true; // No items to deduct

        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $recipe_stmt = $pdo->prepare("SELECT product_id, inventory_item_id, quantity_used FROM recipes WHERE product_id IN ($placeholders)");
        $recipe_stmt->execute($product_ids);
        $recipes = $recipe_stmt->fetchAll();

        $recipe_map = [];
        foreach ($recipes as $recipe) {
            $recipe_map[$recipe['product_id']][] = $recipe;
        }

        $update_stock_stmt = $pdo->prepare("UPDATE inventory_items SET stock_quantity = stock_quantity - ? WHERE id = ?");
        $log_stmt = $pdo->prepare("INSERT INTO inventory_logs (inventory_item_id, admin_id, change_type, quantity_change, old_quantity, new_quantity, notes) VALUES (?, ?, 'used_in_recipe', ?, (SELECT stock_quantity FROM inventory_items WHERE id = ?), (SELECT stock_quantity FROM inventory_items WHERE id = ?) - ?, ?)");
        
        foreach ($order_items as $item) {
            if (isset($recipe_map[$item['product_id']])) {
                foreach ($recipe_map[$item['product_id']] as $ingredient) {
                    $total_to_deduct = $ingredient['quantity_used'] * $item['quantity'];
                    $item_id = $ingredient['inventory_item_id'];
                    $update_stock_stmt->execute([$total_to_deduct, $item_id]);
                    $log_stmt->execute([$item_id, $admin_id, $total_to_deduct, $item_id, $item_id, $total_to_deduct, "Sold in Order #{$order_id}"]);
                }
            }
        }
        return true;
    } catch (Exception $e) {
        // Log the error
        error_log("Inventory deduction failed for order #$order_id: " . $e->getMessage());
        return false;
    }
}
?>
