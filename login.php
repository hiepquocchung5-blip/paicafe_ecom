<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (is_user_logged_in()) {
    header('Location: /profile.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone_number'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Login successful, set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Check if the user was sent here from another page (like checkout)
        $redirect_url = $_SESSION['redirect_url'] ?? '/profile.php';
        unset($_SESSION['redirect_url']); // Clear it after use
        
        header('Location: ' . $redirect_url);
        exit();
    } else {
        $error = "Invalid phone number or password.";
    }
}

include 'includes/header.php';
?>

<div class="flex items-center justify-center min-h-[70vh]">
    <div class="relative flex flex-col md:flex-row m-6 space-y-8 md:space-y-0 bg-white shadow-2xl rounded-2xl">
        
        <div class="flex flex-col justify-center p-8 md:p-14">
            <span class="mb-3 text-4xl font-bold">Welcome Back</span>
            <span class="font-light text-gray-500 mb-8">
                Please enter your details to log in.
            </span>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p><?= e($error) ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['registered'])): ?>
                 <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    <p>Registration successful! Please log in.</p>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="py-4">
                    <label for="phone_number" class="mb-2 text-md font-semibold">Phone Number</label>
                    <input type="tel" name="phone_number" id="phone_number" class="form-input border border-gray-300 rounded-md px-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition" required>
                </div>
                <div class="py-4">
                    <label for="password" class="mb-2 text-md font-semibold">Password</label>
                    <input type="password" name="password" id="password" class="form-input border border-gray-300 rounded-md px-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition" required>
                </div>
                <button type="submit" class="w-full btn-brand py-3 mt-4">
                    Log In
                </button>
            </form>

            <div class="text-center mt-6">
                <p class="text-gray-600">Don't have an account? 
                    <a href="/register.php" class="font-semibold text-orange-600 hover:underline">Register now</a>
                </p>
            </div>
        </div>

        <div class="relative">
            <img src="https://images.unsplash.com/photo-1541167760496-1628856ab772" alt="Image of a latte" class="w-[400px] h-full hidden md:block object-cover rounded-r-2xl">
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>