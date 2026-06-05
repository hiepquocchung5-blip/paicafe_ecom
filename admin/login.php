<?php
// FIX: DO NOT call session_start() here. 
// functions.php will handle it.

// Correct the file paths to go up one directory to the main 'includes' folder.
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

// If an admin is already logged in, redirect them to the admin dashboard
if (is_admin_logged_in()) {
    header('Location: /index.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        // Set session variables
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['user_type'] = $admin['user_type'];
        
        $permissions_stmt = $pdo->prepare("SELECT p.name FROM permissions p JOIN role_permissions rp ON p.id = rp.permission_id WHERE rp.user_type = ?");
        $permissions_stmt->execute([$admin['user_type']]);
        $_SESSION['permissions'] = $permissions_stmt->fetchAll(PDO::FETCH_COLUMN);

        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, action) VALUES (?, ?)");
        $log_stmt->execute([$admin['id'], "Admin user logged in: " . htmlspecialchars($username)]);
        
        // Handle "Remember Me" cookie
        if ($remember_me) {
            setcookie('admin_remember_me', $admin['id'], time() + (86400 * 30), "/admin");
            setcookie('admin_username', $admin['username'], time() + (86400 * 30), "/admin");
        } else {
            if (isset($_COOKIE['admin_remember_me'])) {
                setcookie('admin_remember_me', '', time() - 3600, "/admin");
                setcookie('admin_username', '', time() - 3600, "/admin");
            }
        }
        
        // Redirect to the correct admin dashboard.
        header('Location: /index.php');
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-200 flex items-center justify-center h-screen">
    <div class="w-full max-w-md">
        <form method="POST" class="bg-white shadow-lg rounded-xl px-8 pt-6 pb-8 mb-4">
            <div class="text-center mb-8">
                <i class="fas fa-shield-alt fa-3x text-orange-500"></i>
                <h1 class="text-2xl mt-2 font-bold text-gray-700">Admin Panel Login</h1>
            </div>

            <?php if ($error): ?>
                <p class="bg-red-100 text-red-700 p-3 rounded text-center mb-4 text-sm"><?= e($error) ?></p>
            <?php endif; ?>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Username</label>
                <input class="form-input" id="username" name="username" type="text" placeholder="Admin Username" required value="<?= e($_COOKIE['admin_username'] ?? '') ?>" autocomplete="username">
            </div>
            
            <div class="mb-4" x-data="{ show: false }">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                <div class="relative">
                    <input class="form-input pr-10" id="password" name="password" :type="show ? 'text' : 'password'" placeholder="******************" required autocomplete="current-password">
                    <button typeB="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500">
                        <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="remember_me" class="mr-2 h-4 w-4" <?= isset($_COOKIE['admin_remember_me']) ? 'checked' : '' ?>>
                    <span class="text-sm text-gray-600">Remember me</span>
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