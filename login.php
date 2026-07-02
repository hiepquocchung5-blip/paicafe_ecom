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
        // Login successful, rotate the session ID and set session variables.
        paicafe_login_user($user);
        
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

<div class="flex items-center justify-center min-h-[75vh] py-8">
    <div class="relative flex flex-col md:flex-row m-6 space-y-8 md:space-y-0 bg-white/80 backdrop-blur-md border border-gray-100 shadow-2xl rounded-3xl max-w-4xl overflow-hidden w-full" x-data="{ showPass: false }">
        
        <div class="flex flex-col justify-center p-8 md:p-14 md:w-1/2">
            <span class="mb-3 text-4xl font-extrabold text-gray-800 tracking-tight">Welcome Back</span>
            <span class="font-medium text-gray-400 mb-8 text-sm uppercase tracking-wider">
                Access your Loyalty Account & Orders
            </span>
            
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-xl mb-6 text-sm font-semibold flex items-center space-x-2" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['registered'])): ?>
                 <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-xl mb-6 text-sm font-semibold flex items-center space-x-2" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <span>Registration successful! Please log in.</span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div class="space-y-2">
                    <label for="phone_number" class="text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Phone Number</label>
                    <div class="relative">
                        <i class="fas fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="tel" name="phone_number" id="phone_number" placeholder="e.g. 0912345678"
                               class="w-full bg-gray-50 border border-gray-200 rounded-2xl pl-12 pr-4 py-3.5 text-gray-700 font-bold focus:outline-none focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500 transition-all" required>
                    </div>
                </div>
                
                <div class="space-y-2">
                    <label for="password" class="text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input :type="showPass ? 'text' : 'password'" name="password" id="password" placeholder="••••••••"
                               class="w-full bg-gray-50 border border-gray-200 rounded-2xl pl-12 pr-12 py-3.5 text-gray-700 font-bold focus:outline-none focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500 transition-all" required>
                        <button type="button" @click="showPass = !showPass" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-orange-500 transition-colors">
                            <i class="fas" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full bg-orange-600 hover:bg-orange-500 text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-all shadow-lg shadow-orange-600/20 mt-6 hover:scale-[1.02]">
                    Sign In
                </button>
            </form>

            <div class="text-center mt-8">
                <p class="text-sm font-semibold text-gray-500">Don't have an account? 
                    <a href="/register.php" class="font-bold text-orange-600 hover:text-orange-500 transition-colors hover:underline">Register now</a>
                </p>
            </div>
        </div>

        <div class="relative md:w-1/2 hidden md:block">
            <img src="https://images.unsplash.com/photo-1541167760496-1628856ab772" alt="Image of a latte" class="w-full h-full object-cover animate-pulse-slow">
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-black/20 flex flex-col justify-end p-12 text-white">
                <h3 class="text-2xl font-bold mb-2">PAICAFE Lounge</h3>
                <p class="text-sm text-gray-200">Fresh halal meals, custom coffee creations, and customer loyalty rewards in the heart of Yangon.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
