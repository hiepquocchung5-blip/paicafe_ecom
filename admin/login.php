<?php
// FIX: DO NOT call session_start() here. 
// functions.php will handle it.

// Correct the file paths to go up one directory to the main 'includes' folder.
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
$admin_asset_base = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') === 0) ? '/admin' : '';
$tailwind_css = load_tailwind_css([
    __DIR__ . '/assets/css/tailwind.css',
    dirname(__DIR__) . '/assets/css/tailwind.css',
]);

// If an admin is already logged in, redirect them to the admin dashboard
if (is_admin_logged_in()) {
    header('Location: index.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $permissions_stmt = $pdo->prepare("SELECT p.name FROM permissions p JOIN role_permissions rp ON p.id = rp.permission_id WHERE rp.user_type = ?");
        $permissions_stmt->execute([$admin['user_type']]);
        $permissions = $permissions_stmt->fetchAll(PDO::FETCH_COLUMN);

        paicafe_login_admin($admin, $permissions);

        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, action) VALUES (?, ?)");
        $log_stmt->execute([$admin['id'], "Admin user logged in: " . htmlspecialchars($username)]);
        
        // Handle "Remember Me" cookie
        if ($remember_me) {
            paicafe_set_cookie('admin_remember_me', '1', time() + (86400 * 30), '/admin');
            paicafe_set_cookie('admin_username', $admin['username'], time() + (86400 * 30), '/admin');
        } else {
            if (isset($_COOKIE['admin_remember_me'])) {
                paicafe_clear_cookie('admin_remember_me', '/admin');
                paicafe_clear_cookie('admin_username', '/admin');
            }
        }
        
        // Redirect to the correct admin dashboard.
        header('Location: index.php');
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script>
        (function () {
            const storedTheme = localStorage.getItem('paicafe-theme') || localStorage.getItem('darkMode');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const useDark = storedTheme ? (storedTheme === 'dark' || storedTheme === 'true') : prefersDark;
            document.documentElement.classList.toggle('dark', useDark);
            document.documentElement.classList.toggle('light', !useDark);
            document.documentElement.dataset.theme = useDark ? 'dark' : 'light';
        })();
    </script>
    <style><?= $tailwind_css ?></style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="<?= $admin_asset_base ?>/assets/css/style.css">
    <script src="<?= $admin_asset_base ?>/assets/js/theme.js"></script>
</head>
<body class="liquid-glass-v2 bg-gray-200 dark:bg-slate-950 text-gray-800 dark:text-slate-100 flex items-center justify-center h-screen transition-colors duration-300">
    <button type="button" class="theme-toggle fixed top-4 right-4 h-11 w-11 rounded-full border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-600 dark:text-slate-300 hover:text-orange-600 dark:hover:text-orange-400 transition-colors shadow-md" aria-label="Toggle color theme" title="Toggle theme">
        <i class="fas fa-moon theme-icon-moon"></i>
        <i class="fas fa-sun theme-icon-sun hidden"></i>
    </button>
    <div class="w-full max-w-md">
        <form method="POST" class="bg-white dark:bg-slate-900 shadow-lg rounded-xl px-8 pt-6 pb-8 mb-4 border border-transparent dark:border-slate-800 transition-colors duration-300">
            <?= csrf_field() ?>
            <div class="text-center mb-8">
                <i class="fas fa-shield-alt fa-3x text-orange-500"></i>
                <h1 class="text-2xl mt-2 font-bold text-gray-700 dark:text-slate-100">Admin Panel Login</h1>
            </div>

            <?php if ($error): ?>
                <p class="bg-red-100 text-red-700 p-3 rounded text-center mb-4 text-sm"><?= e($error) ?></p>
            <?php endif; ?>

            <div class="mb-4">
                <label class="block text-gray-700 dark:text-slate-300 text-sm font-bold mb-2" for="username">Username</label>
                <input class="form-input" id="username" name="username" type="text" placeholder="Admin Username" required value="<?= e($_COOKIE['admin_username'] ?? '') ?>" autocomplete="username">
            </div>
            
            <div class="mb-4" x-data="{ show: false }">
                <label class="block text-gray-700 dark:text-slate-300 text-sm font-bold mb-2" for="password">Password</label>
                <div class="relative">
                    <input class="form-input pr-10" id="password" name="password" :type="show ? 'text' : 'password'" placeholder="******************" required autocomplete="current-password">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500">
                        <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="remember_me" class="mr-2 h-4 w-4" <?= isset($_COOKIE['admin_remember_me']) ? 'checked' : '' ?>>
                    <span class="text-sm text-gray-600 dark:text-slate-400">Remember me</span>
                </label>
            </div>

            <div class="flex items-center justify-between">
                <button class="w-full btn-brand" type="submit">
                    Sign In
                </button>
            </div>
        </form>
    </div>
</body>
</html>
