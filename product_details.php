<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: /menu.php");
    exit();
}

// Fetch product details
$stmt = $pdo->prepare("
    SELECT p.*, c.name_en as category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ? AND p.is_available = 1
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: /menu.php");
    exit();
}

// Fetch ingredients
$ingredients_stmt = $pdo->prepare("
    SELECT i.name 
    FROM recipes r
    JOIN inventory_items i ON r.inventory_item_id = i.id
    WHERE r.product_id = ?
    ORDER BY i.name ASC
");
$ingredients_stmt->execute([$product_id]);
$ingredients = $ingredients_stmt->fetchAll(PDO::FETCH_COLUMN);

//  Fetch reviews for this product
$reviews_stmt = $pdo->prepare("
    SELECT r.rating, r.comment, r.created_at, u.username 
    FROM reviews r JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ? ORDER BY r.created_at DESC
");
$reviews_stmt->execute([$product_id]);
$reviews = $reviews_stmt->fetchAll();

//Calculate average rating
$avg_rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as review_count FROM reviews WHERE product_id = ?");
$avg_rating_stmt->execute([$product_id]);
$rating_data = $avg_rating_stmt->fetch();
$avg_rating = $rating_data['avg_rating'] ?? 0;
$review_count = $rating_data['review_count'] ?? 0;

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Back Button: Full-width on mobile for easier tap -->
    <div class="mb-4 sm:mb-6 sticky top-0 z-10 bg-white/80 backdrop-blur-sm pt-4 pb-2 border-b border-gray-200">
        <a href="/menu.php" class="inline-flex items-center text-orange-600 font-semibold hover:underline w-full sm:w-auto justify-center sm:justify-start">
            <i class="fas fa-arrow-left mr-2 text-lg"></i>
            <span class="text-base sm:text-lg">Back to Full Menu</span>
        </a>
    </div>

    <!-- Product Details Card: Stacked on mobile, side-by-side on larger screens -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="grid grid-cols-1 lg:grid-cols-2">
            <!-- Image & Video Tabbed Container: Stacked or toggleable if video exists -->
            <div class="relative overflow-hidden aspect-square lg:aspect-auto" x-data="{ activeMedia: 'image', zoomed: false }">
                <div class="w-full h-full">
                    <template x-if="activeMedia === 'image'">
                        <img 
                            src="<?= e($product['image'] ?: '/assets/uploads/placeholder.png') ?>" 
                            alt="<?= e($product['name_en']) ?>" 
                            class="w-full h-full object-cover transition-transform duration-300 hover:scale-105 cursor-pointer"
                            @click="zoomed = !zoomed"
                            :class="{ 'scale-110': zoomed }"
                            loading="lazy"
                        >
                    </template>
                    <?php if (!empty($product['video_url'])): ?>
                        <template x-if="activeMedia === 'video'">
                            <div class="w-full h-full bg-black flex items-center justify-center">
                                <video src="<?= e($product['video_url']) ?>" class="w-full h-full object-contain" controls autoplay muted loop></video>
                            </div>
                        </template>
                    <?php endif; ?>
                </div>

                <!-- Mobile Zoom Overlay for Image -->
                <div x-show="zoomed" @click="zoomed = false" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4" x-cloak>
                    <img src="<?= e($product['image'] ?: '/assets/uploads/placeholder.png') ?>" alt="<?= e($product['name_en']) ?>" class="max-w-full max-h-full object-contain rounded-2xl">
                </div>

                <?php if (!empty($product['video_url'])): ?>
                    <!-- Media switcher tabs -->
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex bg-black/60 backdrop-blur-md rounded-full p-1 border border-white/10 z-10 shadow-lg">
                        <button @click="activeMedia = 'image'" :class="activeMedia === 'image' ? 'bg-orange-600 text-white' : 'text-gray-300 hover:text-white'" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider transition-all flex items-center space-x-1.5">
                            <i class="fas fa-image"></i> <span>Photo</span>
                        </button>
                        <button @click="activeMedia = 'video'" :class="activeMedia === 'video' ? 'bg-orange-600 text-white' : 'text-gray-300 hover:text-white'" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider transition-all flex items-center space-x-1.5">
                            <i class="fas fa-video"></i> <span>Video</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Content: Flex for dynamic growth, responsive text sizing -->
            <div class="p-4 sm:p-6 lg:p-8 flex flex-col">
                <!-- Title: Smaller on mobile -->
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-2 leading-tight"><?= e($product['name_en']) ?></h1>
                
                <!-- Category: Compact on mobile -->
                <p class="text-sm sm:text-base text-gray-500 mb-3 lg:mb-4">Category: <span class="font-medium"><?= e($product['category_name']) ?></span></p>
                
                <!-- Description: Responsive text, full height on mobile -->
                <div class="text-gray-700 text-base sm:text-lg mb-4 sm:mb-6 flex-grow prose prose-sm sm:prose-base max-w-none">
                    <p><?= e($product['description_en']) ?></p>
                </div>

                <!-- Ingredients: Horizontal scroll on very small screens -->
                <?php if (!empty($ingredients)): ?>
                <div class="mb-4 sm:mb-6">
                    <h3 class="font-semibold text-gray-800 mb-2 text-sm sm:text-base">Main Ingredients:</h3>
                    <div class="flex flex-wrap gap-2 overflow-x-auto pb-2 scrollbar-hide">
                        <?php foreach($ingredients as $ingredient): ?>
                            <span class="bg-gray-200 text-gray-700 text-xs sm:text-sm font-medium px-2 sm:px-3 py-1 rounded-full whitespace-nowrap flex-shrink-0 min-w-0"><?= e($ingredient) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Price & Add to Cart: Fixed bottom on mobile for sticky access -->
                <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 sm:gap-0 mt-auto pt-4 border-t border-gray-100">
                    <div class="flex flex-col items-center sm:items-start">
                        <?php if ($product['discount_percentage'] > 0): ?>
                            <span class="text-sm text-gray-400 line-through"><?= number_format($product['price']) ?> Ks</span>
                            <div class="flex items-center">
                                <span class="text-2xl sm:text-3xl font-bold text-orange-600"><?= number_format($product['price'] - ($product['price'] * $product['discount_percentage'] / 100)) ?> Ks</span>
                                <span class="ml-2 bg-red-100 text-red-600 text-xs font-bold px-2 py-1 rounded">-<?= (float)$product['discount_percentage'] ?>%</span>
                            </div>
                        <?php else: ?>
                            <span class="text-2xl sm:text-3xl font-bold text-orange-600"><?= number_format($product['price']) ?> Ks</span>
                        <?php endif; ?>
                    </div>
                    <button 
                        @click="addToCart(<?= $product['id'] ?>, this)" 
                        :disabled="busy" 
                        class="btn-brand flex-1 sm:flex-none sm:w-auto h-12 sm:h-auto px-6 sm:px-8 rounded-lg font-semibold text-base sm:text-lg flex items-center justify-center order-1 sm:order-2"
                        x-data="{ busy: false }"
                    >
                        <i class="fas fa-shopping-cart mr-2 text-lg"></i>
                        <span x-show="!busy">Add to Cart</span>
                        <span x-show="busy" class="flex items-center">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Adding...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reviews Section: Accordion on mobile for space-saving -->
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 sm:mb-6">
        <h2 class="text-2xl sm:text-3xl font-bold">Customer Reviews (<?= $review_count ?>)</h2>
        <?php if ($review_count > 0): ?>
            <div class="flex items-center space-x-2 mt-2 sm:mt-0 bg-slate-100 dark:bg-slate-800 px-4 py-1.5 rounded-full border border-slate-200 dark:border-slate-700 w-fit">
                <span class="text-sm font-black text-slate-800 dark:text-white"><?= number_format($avg_rating, 1) ?></span>
                <div class="flex text-yellow-400 text-sm">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <i class="<?= $i < round($avg_rating) ? 'fas' : 'far' ?> fa-star mr-0.5"></i>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (is_user_logged_in()): ?>
    <!-- Review Form: Collapsible on mobile -->
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow-md mb-6 sm:mb-8" x-data="{ open: false, rating: 0, comment: '' }" @click.away="open = false">
        <button @click="open = !open" class="w-full text-left flex items-center justify-between mb-2 sm:mb-4">
            <h3 class="text-lg sm:text-xl font-bold">Leave a Review</h3>
            <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="text-orange-600 ml-2"></i>
        </button>
        <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-96" class="overflow-hidden">
            <div class="flex items-center space-x-1 mb-4 pt-4 border-t border-gray-100">
                <template x-for="star in 5">
                    <button 
                        @click="rating = star" 
                        :class="rating >= star ? 'text-yellow-400' : 'text-gray-300'" 
                        class="text-2xl sm:text-3xl focus:outline-none hover:scale-110 transition-transform"
                        aria-label="Rate star"
                    >★</button>
                </template>
            </div>
            <textarea 
                x-model="comment" 
                rows="3" 
                class="form-input w-full mb-4 sm:mb-0" 
                placeholder="Share your thoughts..."
                :disabled="rating === 0"
            ></textarea>
            <button 
                @click="submitReview(<?= $product_id ?>, rating, comment); open = false" 
                :disabled="rating === 0 || comment.trim() === ''" 
                class="btn-brand w-full sm:w-auto mt-2 sm:mt-4"
            >Submit Review</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Reviews List: Cards with swipe-to-expand on mobile if needed -->
    <div class="space-y-4 sm:space-y-6">
        <?php if (empty($reviews)): ?>
        <div class="text-center text-gray-500 py-8">No reviews yet. Be the first to share!</div>
        <?php else: ?>
        <?php foreach ($reviews as $review): ?>
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100" x-data="{ expanded: false }">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-3">
                <span class="font-bold text-sm sm:text-base mb-1 sm:mb-0"><?= e($review['username']) ?></span>
                <div class="flex items-center text-yellow-400 mt-1 sm:mt-0">
                    <?php for ($i = 0; $i < $review['rating']; $i++) echo '<span class="text-yellow-400">★</span>'; ?>
                    <?php for ($i = $review['rating']; $i < 5; $i++) echo '<span class="text-gray-300">★</span>'; ?>
                </div>
            </div>
            <p class="text-gray-600 text-sm sm:text-base leading-relaxed"><?= e($review['comment']) ?></p>
            <!-- Optional: Timestamp or expand for more details -->
            <div class="text-xs text-gray-400 mt-2 flex justify-between">
                <span><?= date('M j, Y', strtotime($review['created_at'])) ?></span>
                <button @click="expanded = !expanded" class="text-orange-600 hover:underline text-xs">Details</button>
            </div>
            <!-- Hidden expanded content if added later -->
            <div x-show="expanded" class="mt-2 pt-2 border-t border-gray-200 text-sm text-gray-500">More details here...</div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>


<script>
// The addToCart script remains the same
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
            document.getElementById('cart-count').textContent = data.cart_count;
            if(document.getElementById('mobile-cart-count')) {
                document.getElementById('mobile-cart-count').textContent = data.cart_count;
            }
        } else {
            Toastify({ text: "Failed to add item: " + (data.message || 'Unknown error'), duration: 3000, gravity: "bottom", position: "right", style: { background: "linear-gradient(to right, #ff5f6d, #ffc371)" } }).showToast();
        }
    })
    .finally(() => {
        setTimeout(() => { alpineComponent.busy = false; }, 300);
    });
}
function submitReview(productId, rating, comment) {
    if (rating === 0) {
        alert('Please select a star rating.');
        return;
    }
    fetch('/api/submit_review.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, rating: rating, comment: comment })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.status === 'success') {
            window.location.reload(); // Refresh to show the new review
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>