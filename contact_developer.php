<?php 
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
include 'includes/header.php';
?>
<div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md text-center">
    <!-- Business Card Image -->
    <img src="https://payvia.asia/assets/images/card.jpg" alt="Payvia Business Card" class="w-full max-w-sm mx-auto mb-6 rounded-lg shadow">

    <!-- Title -->
    <h1 class="text-3xl font-bold">System Developer</h1>
    <p class="text-lg text-gray-600 mt-2">This POS, QR Menu, and E-commerce system was proudly developed by Payvia Software Company.</p>
    
    <!-- Contact Section -->
    <div class="mt-8 text-left space-y-4">
        <p><strong>Contact Us:</strong></p>

        <!-- Phone -->
        <a href="tel:+9592525808066" class="flex items-center text-gray-700 hover:text-orange-600">
            <i class="fas fa-phone fa-fw mr-3 text-orange-500"></i>
            <span>+95 9 9252580 806</span>
        </a>

        <!-- Telegram -->
        <a href="https://t.me/Stephanfilip2k03" target="_blank" class="flex items-center text-gray-700 hover:text-orange-600">
            <i class="fab fa-telegram-plane fa-fw mr-3 text-orange-500"></i>
            <span>Telegram</span>
        </a>

        <!-- Facebook -->
        <a href="https://facebook.com/payviaonlineservices" target="_blank" class="flex items-center text-gray-700 hover:text-orange-600">
            <i class="fab fa-facebook-f fa-fw mr-3 text-orange-500"></i>
            <span>Facebook</span>
        </a>

        <!-- Website -->
        <a href="https://payvia.asia" target="_blank" class="flex items-center text-gray-700 hover:text-orange-600">
            <i class="fas fa-globe fa-fw mr-3 text-orange-500"></i>
            <span>payvia.asia</span>
        </a>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
