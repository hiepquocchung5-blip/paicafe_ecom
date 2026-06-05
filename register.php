<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone_number'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    
    // Get address fields from the form
    $street_address = trim($_POST['street_address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');

    // Validation
    if (empty($username) || empty($email) || empty($phone) || empty($password)) {
        $errors[] = "All required fields must be filled.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    if (!$terms) {
        $errors[] = "You must accept the terms and services.";
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone_number = ? OR email = ?");
        $stmt->execute([$phone, $email]);
        if ($stmt->fetch()) {
            $errors[] = "An account with this phone number or email already exists.";
        }
    }

    // Process registration
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Add address columns to the INSERT statement
        $stmt = $pdo->prepare(
            "INSERT INTO users (username, email, phone_number, password, street_address, city, country) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        if ($stmt->execute([$username, $email, $phone, $hashed_password, $street_address, $city, $country])) {
            $log_stmt = $pdo->prepare("INSERT INTO activity_logs (action) VALUES (?)");
            $log_stmt->execute(["New user registered: " . htmlspecialchars($username)]);
            header('Location: login.php?registered=true');
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

include 'includes/header.php';
?>

<div class="flex items-center justify-center min-h-[70vh]">
    <div class="relative flex flex-col md:flex-row m-6 space-y-8 md:space-y-0 bg-white shadow-2xl rounded-2xl">
        
        <div class="flex flex-col justify-center p-8 md:p-14">
            <span class="mb-3 text-4xl font-bold">Create an Account</span>
            <span class="font-light text-gray-500 mb-8">
                Join us to start earning loyalty points!
            </span>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?= e($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="py-2">
                    <label for="username" class="mb-2 text-md font-semibold">Username</label>
                    <input type="text" name="username" id="username" class="form-input  border border-gray-300 rounded-md px-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition" value="<?= e($_POST['username'] ?? '') ?>" required>
                </div>
                <div class="py-2">
                    <label for="email" class="mb-2 text-md font-semibold">Email</label>
                    <input type="email" name="email" id="email" class="form-input  border border-gray-300 rounded-md px-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition" value="<?= e($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="py-2">
                    <label for="phone_number" class="mb-2 text-md font-semibold">Phone Number</label>
                    <input type="tel" name="phone_number" id="phone_number" class="form-input  border border-gray-300 rounded-md px-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition" value="<?= e($_POST['phone_number'] ?? '') ?>" required>
                </div>

                <div class="py-2 border-t mt-4 pt-4">
                    <p class="mb-2 font-semibold">Delivery Address (Optional)</p>
                    <div class="space-y-2">
                        <input type="text" name="street_address" placeholder="Street Address" class="form-input  border border-gray-300 rounded-md px-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition" value="<?= e($_POST['street_address'] ?? '') ?>">
                        <input type="text" name="city" placeholder="City" class="form-input  border border-gray-300 rounded-md px-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition" value="<?= e($_POST['city'] ?? '') ?>">
                        <input type="text" name="country" placeholder="Country" class="form-input  border border-gray-300 rounded-md px-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition" value="<?= e($_POST['country'] ?? '') ?>">
                    </div>
                </div>

                <div class="py-2 border-t mt-4 pt-4">
                     <div class="py-2">
                        <label for="password" class="mb-2 text-md font-semibold">Password</label>
                        <input type="password" name="password" id="password" class="form-input  border border-gray-300 rounded-md px-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition" required>
                    </div>
                    <div class="py-2">
                        <label for="confirm_password" class="mb-2 text-md font-semibold">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-input  border border-gray-300 rounded-md px-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition" required>
                    </div>
                </div>

                <div class="py-4" x-data="{ open: false }">
                    <label class="flex items-center">
                        <input type="checkbox" name="terms" class="mr-2 h-4 w-4">
                        <span>I accept the <a href="#" @click.prevent="open = true" class="font-semibold text-orange-600 hover:underline">Terms & Services</a></span>
                    </label>
                    <div x-show="open" @click.away="open = false" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" style="display: none;">
                        <div class="bg-white p-6 rounded-lg max-w-lg w-full">
                            <h2 class="text-xl font-bold mb-4">Terms and Services</h2>
                            <p class="text-gray-600">Your terms of service content goes here.</p>
                            <button @click="open = false" class="mt-6 btn-outline w-full">Close</button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full btn-brand py-3 mt-2">
                    Register
                </button>
            </form>

            <div class="text-center mt-6">
                <p class="text-gray-600">Already have an account? 
                    <a href="/login.php" class="font-semibold text-orange-600 hover:underline">Log in</a>
                </p>
            </div>
        </div>

        <div class="relative">
            <img src="https://images.unsplash.com/photo-1599939963982-7ed67b6122ed" alt="Image of coffee beans" class="w-[400px] h-full hidden md:block object-cover rounded-r-2xl">
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>