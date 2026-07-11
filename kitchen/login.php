<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// If a kitchen user is already logged in, redirect them to the display system
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'kitchen') {
    header('Location: /kitchen/index.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // CRITICAL: This query specifically checks for a user with the 'kitchen' role.
    // An 'admin' or 'staff' user will NOT be found by this query.
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND user_type = 'kitchen'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    

    if ($user && password_verify($password, $user['password'])) {
        // Login successful, rotate the session ID and save details to the session.
        paicafe_login_admin($user, []);
        
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, action) VALUES (?, ?)");
        $log_stmt->execute([$user['id'], "Kitchen user logged in: " . htmlspecialchars($username)]);
        
        header('Location: /index.php'); // Redirect to the main kitchen page
        exit();
    } else {
        // Login failed, set an error message
        $error = "Invalid credentials or not a kitchen account.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Login</title>
    <meta name="robots" content="noindex, nofollow, noarchive">
    <link rel="stylesheet" href="https://paicafes.com/assets/css/tailwind.css?v=<?= filemtime(__DIR__ . '/../assets/css/tailwind.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-gray-800 flex items-center justify-center h-screen">
    <div class="w-full max-w-sm">
        <form method="POST" class="bg-white shadow-lg rounded-xl px-8 pt-6 pb-8 mb-4">
            <div class="text-center mb-8">
                <i class="fas fa-utensils fa-3x text-orange-500"></i>
                <h1 class="text-2xl mt-2 font-bold text-gray-700">Kitchen Display Login</h1>
            </div>

             <?php if ($error): ?>
                <p class="bg-red-100 text-red-700 p-3 rounded text-center mb-4 text-sm"><?= e($error) ?></p>
            <?php endif; ?>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Username</label>
                <input class="shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-orange-500" id="username" name="username" type="text" placeholder="Kitchen Username" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                <input class="shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-orange-500" id="password" name="password" type="password" placeholder="******************" required>
            </div>
            <div class="flex items-center justify-between">
                <button class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded w-full focus:outline-none focus:shadow-outline" type="submit">
                    Sign In
                </button>
            </div>
        </form>
    </div>
</body>
</html>
