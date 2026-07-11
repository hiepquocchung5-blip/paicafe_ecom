<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
$page_title = 'Pai Cafe Yangon | Halal Cafe & Restaurant in Thuwunna, Thingangyun';
$page_description = 'Visit Pai Cafe in Thuwunna, Thingangyun, Yangon for halal meals, specialty coffee, burgers, pasta and desserts. Browse our QR menu or reserve a table.';
$page_keywords = 'cafe Yangon, cafe Thuwunna, cafe Thingangyun, halal cafe Yangon, restaurant Thuwunna, coffee shop Thingangyun, Pai Cafe, cafe near me Yangon';
$page_canonical = 'https://paicafes.com/home.php';
include 'includes/header.php';

// --- Fetch Today's Special Products ---
$specials = paicafe_cache_remember('home:specials', 300, function () use ($pdo) { return $pdo->query("
    SELECT p.*, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
    FROM products p
    LEFT JOIN reviews r ON p.id = r.product_id
    WHERE p.is_special_today = 1 AND p.is_available = 1
    GROUP BY p.id
    LIMIT 3
")->fetchAll(); });

// --- Fetch Top-Rated Products (Favorites) ---
$favorites = paicafe_cache_remember('home:favorites', 300, function () use ($pdo) { return $pdo->query("
    SELECT p.*, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
    FROM products p
    JOIN reviews r ON p.id = r.product_id
    WHERE p.is_available = 1
    GROUP BY p.id
    HAVING avg_rating >= 3.5
    ORDER BY avg_rating DESC, COUNT(r.id) DESC
    LIMIT 3
")->fetchAll(); });

// --- Fetch Active Loyalty Rewards ---
$rewards = paicafe_cache_remember('home:rewards', 600, function () use ($pdo) { return $pdo->query("SELECT * FROM loyalty_rewards WHERE is_active = 1 ORDER BY points_cost ASC LIMIT 4")->fetchAll(); });

// --- Fetch Recent Reviews for Testimonial Section ---
$recent_reviews = paicafe_cache_remember('home:reviews', 180, function () use ($pdo) { return $pdo->query("
    SELECT r.*, p.name_en as product_name, p.image as product_image, u.username
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
    LIMIT 3
")->fetchAll(); });
?>

<!-- Section 1: Hero Section -->
<div class="cafe-hero relative bg-gray-800 text-white rounded-lg shadow-2xl overflow-hidden mb-20">
    <img src="https://paicafes.com/assets/uploads/bgg.png" class="absolute inset-0 w-full h-full object-cover opacity-40" alt="Cafe background">
    <div class="relative z-10 flex flex-col items-center justify-center h-full text-center p-8">
        <div class="cafe-hero__brand" aria-label="Pai Cafe and Lounge"><img src="/assets/svg/pai-mark.svg" alt=""><span><strong>PAI</strong><small>Cafe &amp; Lounge</small></span></div>
        <span class="cafe-hero__eyebrow">Halal café & restaurant · Yangon</span>
        <h1 class="text-4xl md:text-6xl font-extrabold leading-tight mb-4">Good food. Great coffee.<br><span>A place to slow down.</span></h1>
        <p class="text-lg md:text-xl max-w-2xl mb-8">Fresh halal meals, handcrafted drinks, and warm hospitality—served every day at Paicafe.</p>
        <div class="cafe-hero__actions">
            <a href="/menu.php" class="btn-brand text-lg">Explore Our Menu</a>
            <a href="/reserve_table.php" class="cafe-hero__secondary text-lg">Reserve a Table</a>
        </div>
        <div class="cafe-hero__facts" aria-label="Restaurant information"><span><i class="fas fa-clock"></i> Open daily · 9 AM–6 PM</span><span><i class="fas fa-location-dot"></i> Thuwunna, Yangon</span><span><i class="fas fa-certificate"></i> Halal kitchen</span></div>
    </div>
</div>

<div class="flex justify-center -mt-12 mb-12">
    <img width="64" height="64" src="https://img.icons8.com/external-doodles-line-amoghdesign/64/EA580C/external-food-islam-doodles-line-amoghdesign.png" alt="Halal Food Icon"/>
</div>

<section class="cafe-highlights" aria-label="Why visit Pai Cafe">
    <article><i class="fas fa-mug-hot"></i><div><strong>Specialty coffee</strong><span>Freshly crafted hot and iced drinks</span></div></article>
    <article><i class="fas fa-certificate"></i><div><strong>Halal kitchen</strong><span>Comfort food prepared with care</span></div></article>
    <article><i class="fas fa-location-dot"></i><div><strong>Easy to find</strong><span>Thuwunna, Thingangyun, Yangon</span></div></article>
    <article><i class="fas fa-mobile-screen-button"></i><div><strong>Quick QR ordering</strong><span>Browse, order and track from your phone</span></div></article>
</section>

<!-- Section 2: Today's Specials -->
<div class="text-center mb-12 animate-on-scroll" x-data="{ featuredProduct: null, showModal: false }">
    <h2 class="text-4xl font-bold text-gray-800">Today's Specials</h2>
    <p class="text-lg text-gray-600">Handpicked for you, with the freshest ingredients.</p>
    
   <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mt-8 mb-20">
        <?php foreach ($specials as $product): ?>
        <div class="bg-white rounded-lg shadow-lg overflow-hidden flex flex-col transform hover:-translate-y-2 transition-transform duration-300" x-data="{ busy: false }">
            <a href="product_details.php?id=<?= e($product['id']) ?>">
                <img src="<?= e($product['image'] ?: '/assets/uploads/placeholder.png') ?>" alt="<?= e($product['name_en']) ?>" class="w-full h-56 object-cover">
            </a>
            <div class="p-6 flex-grow flex flex-col">
                <h2 class="text-2xl font-bold mb-2 flex-grow"><?= e($product['name_en']) ?></h2>
                <div class="flex items-center text-xs text-gray-500 mb-4">
                    <?php if ($product['avg_rating'] > 0): ?>
                        <span class="text-yellow-400 mr-1">
                            <?= str_repeat('★', round($product['avg_rating'])) ?><span class="text-gray-300"><?= str_repeat('★', 5 - round($product['avg_rating'])) ?></span>
                        </span>
                        <span class="font-bold text-gray-600">(<?= number_format($product['avg_rating'], 1) ?>)</span>
                    <?php else: ?>
                        <span class="text-gray-400 italic">No reviews yet</span>
                    <?php endif; ?>
                </div>
                <div class="flex justify-between items-center mt-auto">
                    <span class="text-2xl font-bold text-orange-600"><?= e($product['price']) ?> Ks</span>
                    <button @click="addToCart(<?= $product['id'] ?>, $data)" :disabled="busy" class="btn-brand">
                        <i class="fas fa-shopping-cart mr-2"></i> Add to Cart
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
        <?php if (!empty($favorites)): ?>
<div class="bg-gray-100 py-20">
    <div class="container mx-auto">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-800">Customer Favorites</h2>
            <p class="text-lg text-gray-600">Discover the dishes our customers love the most!</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach($favorites as $index => $product): ?>
            <div class="animate-on-scroll bg-white rounded-xl shadow-md overflow-hidden flex flex-col" style="transition-delay: <?= $index * 150 ?>ms;">
                <img src="<?= e($product['image'] ?: '/assets/uploads/placeholder.png') ?>" class="w-full h-48 object-cover">
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <h3 class="text-xl font-bold"><?= e($product['name_en']) ?></h3>
                        <div class="flex items-center text-yellow-400 font-bold">
                            <span><?= number_format($product['avg_rating'], 1) ?></span>
                            <span class="ml-1 text-sm">
                                <?= str_repeat('★', round($product['avg_rating'])) ?><span class="text-gray-300"><?= str_repeat('★', 5 - round($product['avg_rating'])) ?></span>
                            </span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2 line-clamp-2"><?= e($product['description_en']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Testimonial Section: Customer Reviews -->
<?php if (!empty($recent_reviews)): ?>
<div class="bg-white py-20 border-t border-b border-gray-100">
    <div class="container mx-auto">
        <div class="text-center mb-12 animate-on-scroll">
            <h2 class="text-4xl font-bold text-gray-800">What Our Customers Say</h2>
            <p class="text-lg text-gray-600">Real feedback from recent visitors at PAICAFE</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach($recent_reviews as $index => $rev): ?>
            <div class="animate-on-scroll bg-gray-50 p-6 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 border border-gray-100 flex flex-col justify-between" style="transition-delay: <?= $index * 150 ?>ms;">
                <div>
                    <div class="flex items-center space-x-1 text-yellow-400 mb-3">
                        <?php for ($i = 0; $i < $rev['rating']; $i++) echo '★'; ?>
                        <?php for ($i = $rev['rating']; $i < 5; $i++) echo '<span class="text-gray-300">★</span>'; ?>
                    </div>
                    <p class="text-gray-600 italic text-sm sm:text-base mb-6 leading-relaxed">"<?= e($rev['comment'] ?: 'Great food and amazing hospitality!') ?>"</p>
                </div>
                <div class="flex items-center space-x-3 pt-4 border-t border-gray-200/60">
                    <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center font-bold text-orange-600 text-sm">
                        <?= e(strtoupper(substr($rev['username'], 0, 1))) ?>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 text-sm"><?= e($rev['username']) ?></h4>
                        <p class="text-[10px] text-gray-400">Reviewed: <span class="font-semibold text-orange-600"><?= e($rev['product_name']) ?></span></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>
    <div class="bg-white py-20">
    <div class="container mx-auto text-center">
        <div class="max-w-2xl mx-auto">
            <h2 class="text-4xl font-bold text-gray-800 mb-4 animate-on-scroll">Book Your Table</h2>
            <p class="text-lg text-gray-600 mb-8 animate-on-scroll" style="transition-delay: 150ms;">
                Planning a visit? Reserve your favorite spot in advance and enjoy a seamless dining experience with us. Perfect for meetings, gatherings, or a quiet coffee break.
            </p>
            <div class="animate-on-scroll" style="transition-delay: 300ms;">
                <a href="/reserve_table.php" class="btn-brand text-lg">
                    <i class="fas fa-calendar-check mr-2"></i> Reserve a Table Now
                </a>
            </div>
        </div>
    </div>
</div>

<div class="bg-gray-100 py-20">
    <div class="container mx-auto">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-800">Order in 4 Easy Steps</h2>
            <p class="text-lg text-gray-600">Enjoy seamless ordering right from your table.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
            <div class="animate-on-scroll"><div class="bg-orange-500 text-white rounded-full h-16 w-16 flex items-center justify-center font-bold text-3xl mx-auto mb-4">1</div><h3 class="font-bold text-xl mb-2">Scan Code</h3><p class="text-gray-600">Use your phone to scan the QR code on your table.</p></div>
            <div class="animate-on-scroll" style="transition-delay: 150ms;"><div class="bg-orange-500 text-white rounded-full h-16 w-16 flex items-center justify-center font-bold text-3xl mx-auto mb-4">2</div><h3 class="font-bold text-xl mb-2">Browse Menu</h3><p class="text-gray-600">Choose your favorite food and drinks.</p></div>
            <div class="animate-on-scroll" style="transition-delay: 300ms;"><div class="bg-orange-500 text-white rounded-full h-16 w-16 flex items-center justify-center font-bold text-3xl mx-auto mb-4">3</div><h3 class="font-bold text-xl mb-2">Place Order</h3><p class="text-gray-600">Confirm your items in the cart and checkout.</p></div>
            <div class="animate-on-scroll" style="transition-delay: 450ms;"><div class="bg-orange-500 text-white rounded-full h-16 w-16 flex items-center justify-center font-bold text-3xl mx-auto mb-4">4</div><h3 class="font-bold text-xl mb-2">Enjoy!</h3><p class="text-gray-600">Your delicious order will be brought to you shortly.</p></div>
        </div>
    </div>
</div>

<!-- Section 3: Loyalty Rewards -->
<div class="text-center mb-12">
    <!-- FIX: Replaced placeholder icon with a clean SVG gift icon -->
    <h2 class="text-4xl font-bold text-gray-800 flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mr-3 text-purple-600" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M5 5a3 3 0 013-3h4a3 3 0 013 3v2h-2V5a1 1 0 00-1-1H8a1 1 0 00-1 1v2H5V5zm10 4H5a1 1 0 00-1 1v6a1 1 0 001 1h10a1 1 0 001-1v-6a1 1 0 00-1-1zM9 14a1 1 0 11-2 0 1 1 0 012 0zm4 0a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd" />
        </svg>
        Redeemable Rewards
    </h2>
    <p class="text-lg text-gray-600">A few of the rewards you can earn with loyalty points!</p>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-20">
    <?php foreach ($rewards as $reward): ?>
    <div class="bg-gradient-to-br from-purple-500 to-indigo-600 text-white p-6 rounded-xl shadow-lg flex flex-col text-center">
       <!-- FIX: Replaced placeholder icon with the SVG pi symbol -->
<div class="mb-4 text-indigo-200">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" viewBox="0 0 64 64" fill="black">
    <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="serif" font-size="48" fill="black">
      π
    </text>
  </svg>
</div>

        <h3 class="text-xl font-bold mb-2 flex-grow"><?= e($reward['title']) ?></h3>
        <div class="mt-auto bg-white text-indigo-600 font-bold py-2 px-4 rounded-full">
            <?= e($reward['points_cost']) ?> Points
        </div>
    </div>
    <?php endforeach; ?>
</div>
<div class="text-center">
    <a href="/rewards.php" class="text-orange-600 font-semibold hover:underline">View All Rewards &rarr;</a>
</div>


<!-- Section 4: Contact Us & Location -->
<div class="mt-24">
    <div class="text-center mb-12">
        <h2 class="text-4xl font-bold text-gray-800">Visit Us</h2>
        <p class="text-lg text-gray-600">Your neighborhood café and halal restaurant in Thuwunna, Thingangyun, Yangon.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center bg-white p-8 rounded-lg shadow-xl">
        <div>
            <h3 class="text-2xl font-bold mb-4">Contact Information</h3>
            <div class="space-y-3 text-gray-700">
                <p><i class="fas fa-map-marker-alt fa-fw mr-2 text-orange-500"></i> No.88,Thantumar Main Street, Thuwunna Tsp, Yangon, Myanmar</p>
                <p><i class="fas fa-phone fa-fw mr-2 text-orange-500"></i> +95 9 8 9 0 9 0 7 7 2 4</p>
                <p><i class="fas fa-envelope fa-fw mr-2 text-orange-500"></i> contact@paicafes.com</p>
                <p><i class="fas fa-clock fa-fw mr-2 text-orange-500"></i> Open Daily: 9:00 AM - 6:00 PM</p>
            </div>
        </div>
        <div>
            <div class="rounded-lg overflow-hidden shadow-lg">
                <!-- FIX: Using the correct map for Pai Cafe & Lounge -->
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3819.1772366972123!2d96.19470207519767!3d16.817561283975696!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30c1eda9ca726379%3A0xb64990e905985f8b!2sPai%20Cafe%20%26%20Lounge!5e0!3m2!1sen!2smm!4v1757861178248!5m2!1sen!2smm" width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
    function addToCart(productId, alpineComponent) {
        alpineComponent.busy = true;
        fetch('/api/cart_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', product_id: productId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Toastify({ text: "Added to cart!", duration: 3000, gravity: "bottom", position: "right", style: { background: "linear-gradient(to right, #00b09b, #96c93d)" } }).showToast();
                // Update cart counts in header
                document.getElementById('cart-count').textContent = data.cart_count;
                if (document.getElementById('mobile-cart-count')) {
                    document.getElementById('mobile-cart-count').textContent = data.cart_count;
                }
            } else {
                Toastify({ text: "Failed to add item.", duration: 3000, gravity: "bottom", position: "right", style: { background: "linear-gradient(to right, #ff5f6d, #ffc371)" } }).showToast();
            }
        })
        .finally(() => {
            setTimeout(() => { alpineComponent.busy = false; }, 300);
        });
    }
</script>

<?php include 'includes/footer.php'; ?>
