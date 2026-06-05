<?php 
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';
// Set the HTTP response code to 404 Not Found
http_response_code(404);
require 'includes/header.php'; 
?>

<div class="flex flex-col items-center justify-center text-center">
    <div class="w-full max-w-lg mb-8">
        <?php include __DIR__ . '/assets/svg/404-illustration.svg'; ?>
    </div>

    <h1 class="text-4xl font-bold text-gray-800">Page Not Found</h1>
    <p class="text-lg text-gray-600 mt-4 max-w-md">
        Sorry, we couldn't find the page you're looking for. It might have been moved, or maybe the link is incorrect.
    </p>
    <div class="mt-8">
        <a href="/home.php" class="btn-brand">Go Back to Homepage</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>