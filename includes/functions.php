<?php
/**
 * Main Functions File for Paicafe Application
 * This is the SINGLE source of truth for all functions (public and admin).
 */

// 1. SESSION MANAGEMENT
function paicafe_is_https() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
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

/**
 * Formats a number as currency.
 */
function format_currency($amount, $currency = 'Ks') {
    return number_format($amount, 0) . ' ' . $currency;
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
        header('Location: /admin/login.php');
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
