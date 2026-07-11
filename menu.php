<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$current_page = 'menu';
$page_title = 'Menu | Pai Cafe Thuwunna, Yangon';
$page_description = 'Browse halal meals, specialty coffee, burgers, pasta and café favorites from Pai Cafe in Thuwunna, Thingangyun, Yangon.';
$page_canonical = APP_URL . '/menu.php';

$table_number = null; 

// --- QR Code Logic ---
if (isset($_GET['qr_table_id_menu'])) {
    $table_identifier = $_GET['qr_table_id_menu'];
    $stmt = $pdo->prepare("SELECT id, table_number FROM tables WHERE qr_code_identifier = ?");
    $stmt->execute([$table_identifier]);
    $table = $stmt->fetch();
    if ($table) {
        $_SESSION['table_id'] = $table['id'];
        $table_number = $table['table_number']; 
        unset($_SESSION['user_id']); 
    }
} elseif (isset($_SESSION['table_id'])) {
    $stmt = $pdo->prepare("SELECT table_number FROM tables WHERE id = ?");
    $stmt->execute([$_SESSION['table_id']]);
    $table_number = $stmt->fetchColumn();
}

// --- Data Fetching ---
$categories = $pdo->query("SELECT * FROM categories ORDER BY name_en ASC")->fetchAll();
$products_stmt = $pdo->query("
    SELECT 
        p.*, 
        c.name_en AS category_name,
        AVG(r.rating) AS avg_rating 
    FROM products p
    LEFT JOIN reviews r ON p.id = r.product_id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_available = 1
    GROUP BY p.id
    ORDER BY p.category_id, p.name_en ASC
");

$products = $products_stmt->fetchAll();
$user_favorites = [];
if (is_user_logged_in()) {
    $fav_stmt = $pdo->prepare("SELECT product_id FROM user_favorites WHERE user_id = ?");
    $fav_stmt->execute([$_SESSION['user_id']]);
    $user_favorites = $fav_stmt->fetchAll(PDO::FETCH_COLUMN);
}
$products_by_category = [];
foreach ($products as $product) { $products_by_category[$product['category_id']][] = $product; }

// FIX: Corrected combo query to get all needed data
$combos_stmt = $pdo->query("
    SELECT c.*, GROUP_CONCAT(p.name_en SEPARATOR ' + ') as product_names, SUM(p.price) as original_total
    FROM combos c
    JOIN combo_products cp ON c.id = cp.combo_id
    JOIN products p ON cp.product_id = p.id
    WHERE c.is_active = 1 GROUP BY c.id
");
$combos = $combos_stmt->fetchAll();

include 'includes/header.php';
?>

<div class="menu-page max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    products: <?= htmlspecialchars(json_encode($products)) ?>, 
    categories: <?= htmlspecialchars(json_encode($categories)) ?>,
    selectedCategory: 'All',
    searchTerm: '',
    sortBy: 'recommended',
    offersOnly: false,
    init() {
        const params = new URLSearchParams(location.search);
        const category = params.get('category');
        if (category && this.categories.some(item => item.name_en === category)) this.selectedCategory = category;
        this.searchTerm = params.get('search') || '';
        this.sortBy = ['recommended', 'price-low', 'price-high', 'rating'].includes(params.get('sort')) ? params.get('sort') : 'recommended';
        this.offersOnly = params.get('offers') === '1';
        this.$watch('selectedCategory', () => this.syncUrl());
        this.$watch('searchTerm', () => this.syncUrl());
        this.$watch('sortBy', () => this.syncUrl());
        this.$watch('offersOnly', () => this.syncUrl());
    },
    syncUrl() {
        const params = new URLSearchParams(location.search);
        ['category', 'search', 'sort', 'offers'].forEach(key => params.delete(key));
        if (this.selectedCategory !== 'All') params.set('category', this.selectedCategory);
        if (this.searchTerm.trim()) params.set('search', this.searchTerm.trim());
        if (this.sortBy !== 'recommended') params.set('sort', this.sortBy);
        if (this.offersOnly) params.set('offers', '1');
        history.replaceState({}, '', `${location.pathname}${params.toString() ? '?' + params : ''}`);
    },
    resetFilters() { this.selectedCategory = 'All'; this.searchTerm = ''; this.sortBy = 'recommended'; this.offersOnly = false; },
    finalPrice(product) { return Number(product.price) * (1 - Number(product.discount_percentage || 0) / 100); },
    get filteredProducts() {
        let items = this.products;
        if (this.selectedCategory !== 'All') {
            items = items.filter(p => p.category_name === this.selectedCategory);
        }
        if (this.searchTerm.trim() !== '') {
            items = items.filter(p => 
                p.name_en.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                (p.description_en && p.description_en.toLowerCase().includes(this.searchTerm.toLowerCase()))
            );
        }
        if (this.offersOnly) items = items.filter(p => Number(p.discount_percentage || 0) > 0);
        items = [...items];
        if (this.sortBy === 'price-low') items.sort((a, b) => this.finalPrice(a) - this.finalPrice(b));
        if (this.sortBy === 'price-high') items.sort((a, b) => this.finalPrice(b) - this.finalPrice(a));
        if (this.sortBy === 'rating') items.sort((a, b) => Number(b.avg_rating || 0) - Number(a.avg_rating || 0));
        return items;
    }
        }">
    <header class="menu-intro">
        <span>Freshly made · Halal kitchen</span>
        <h1>Find your favorite</h1>
        <p>Comfort food, café classics and handcrafted drinks—ready to order.</p>
    </header>
    <?php if ($table_number): ?>
  <div class="sticky top-16 right-4 z-30 flex justify-end">
    <div class="bg-orange-600 text-white font-semibold text-sm sm:text-base px-4 py-2 rounded-full shadow-lg flex items-center space-x-2 select-none">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-3-3v6m7 3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span>Table</span>
      <span class="text-lg sm:text-xl font-bold"><?= e($table_number) ?></span>
    </div>
  </div>
<?php endif; ?>


    <div class="menu-toolbar sticky top-0 bg-white/95 backdrop-blur-md z-20 py-4 border-b border-gray-200 mb-4 sm:mb-8">
        <div class="relative mb-4">
            <input type="search" x-model.debounce.200ms="searchTerm" placeholder="Search coffee, burgers, noodles…" aria-label="Search menu" class="form-input w-full pl-10 pr-12 py-3 rounded-xl text-base shadow-sm focus:ring-2 focus:ring-orange-500">
            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            <button type="button" x-show="searchTerm" @click="searchTerm = ''" class="menu-search-clear" aria-label="Clear search"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="flex space-x-2 overflow-x-auto" style="-ms-overflow-style: none; scrollbar-width: none;">
            <button @click="selectedCategory = 'All'; searchTerm = ''" :class="{ 'bg-orange-500 text-white shadow-md': selectedCategory === 'All', 'bg-gray-100 hover:bg-gray-200': selectedCategory !== 'All' }" class="px-3 sm:px-4 py-2 rounded-lg font-semibold flex-shrink-0 text-sm sm:text-base transition-all duration-200">All</button>
            <template x-for="category in categories" :key="category.id">
                <button @click="selectedCategory = category.name_en; searchTerm = ''" :class="{ 'bg-orange-500 text-white shadow-md': selectedCategory === category.name_en, 'bg-gray-100 hover:bg-gray-200': selectedCategory !== category.name_en }" x-text="category.name_en" class="px-3 sm:px-4 py-2 rounded-lg font-semibold flex-shrink-0 text-sm sm:text-base transition-all duration-200"></button>
            </template>
        </div>
        <div class="menu-filter-row">
            <label class="menu-offer-toggle"><input type="checkbox" x-model="offersOnly"><span><i class="fas fa-tag"></i> Offers</span></label>
            <label class="menu-sort"><span>Sort</span><select x-model="sortBy" aria-label="Sort menu items"><option value="recommended">Recommended</option><option value="rating">Top rated</option><option value="price-low">Price: low to high</option><option value="price-high">Price: high to low</option></select></label>
        </div>
        <div class="menu-result-meta"><p class="menu-result-count"><strong x-text="filteredProducts.length"></strong> items available</p><button x-show="selectedCategory !== 'All' || searchTerm || offersOnly || sortBy !== 'recommended'" @click="resetFilters()">Reset filters</button></div>
    </div>

    <div class="menu-empty" x-show="filteredProducts.length === 0" x-cloak>
        <svg viewBox="0 0 64 64" aria-hidden="true"><path d="M19 7v20M13 7v12c0 5 12 5 12 0V7M19 27v30M43 7c-8 9-8 24 0 26v24M43 7v26" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <h2>No dishes found</h2><p>Try another name or choose a different category.</p>
        <button @click="resetFilters()">Show all menu items</button>
    </div>
    
    <?php if (!empty($combos)): ?>
    <div class="mb-8 sm:mb-12">
        <h2 class="text-2xl sm:text-3xl font-semibold border-b-2 border-purple-500 pb-2 mb-4 sm:mb-6">Special Combos</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
            <?php foreach ($combos as $combo): 
                $final_price = $combo['original_total'] - $combo['discount_amount'];
            ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden flex flex-col transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1" x-data="{ busy: false }">
                <div class="p-4 sm:p-6 flex-grow flex flex-col">
                    <h3 class="text-xl sm:text-2xl font-bold mb-2 leading-tight"><?= e($combo['name']) ?></h3>
                    <p class="text-xs sm:text-sm text-gray-500 mb-4 flex-grow line-clamp-3"><?= e($combo['product_names']) ?></p>
                    <div class="mt-auto text-right"><p class="text-gray-500 text-xs sm:text-sm line-through"><?= number_format($combo['original_total'], 2) ?> Ks</p><p class="text-xl sm:text-2xl font-bold text-orange-600"><?= number_format($final_price, 2) ?> Ks</p></div>
                    <button @click="addComboToCart(<?= $combo['id'] ?>, this)" :disabled="busy" class="btn-brand w-full mt-4 h-10 sm:h-auto px-4 sm:px-6 rounded-lg font-semibold text-sm sm:text-base flex items-center justify-center transition-colors">
                        <span x-show="!busy">Add Combo</span><span x-show="busy"><i class="fas fa-spinner fa-spin mr-2"></i>Adding...</span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
            </div>
        <?php endif; ?>
    
        <?php foreach ($categories as $category): ?>
        <div class="mb-8 sm:mb-12"
         x-show="(selectedCategory === 'All' || selectedCategory === '<?= e($category['name_en']) ?>') && filteredProducts.some(p => Number(p.category_id) === <?= (int)$category['id'] ?>)">

        <h2 class="text-2xl sm:text-3xl font-semibold border-b-2 border-orange-500 pb-2 mb-4 sm:mb-6">
        <?= e($category['name_en']) ?>
     </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
        <template x-for="product in filteredProducts.filter(p => p.category_id === <?= (int)$category['id'] ?>)" :key="product.id">
            <div class="menu-product-card bg-white rounded-xl shadow-md overflow-hidden flex flex-col transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group"
                 x-data="{ busy: false, isFavorite: <?= json_encode($user_favorites) ?>.includes(product.id) }">

                <a :href="`product_details.php?id=${product.id}`" class="block relative overflow-hidden">
                    <img :src="product.image || '/assets/uploads/placeholder.png'" 
                         :alt="product.name_en"
                         class="w-full h-48 sm:h-56 object-cover transition-transform duration-300 group-hover:scale-105"
                         loading="lazy">
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all lg:hidden flex items-end p-4">
                        <button @click.prevent="addToCart(product.id, $data)" 
                                :disabled="busy" 
                                class="btn-brand w-full h-10 text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity">
                            Quick Add
                        </button>
                    </div>
                </a>

                <div class="p-4 sm:p-6 flex-grow flex flex-col">
                    <div class="flex justify-between items-start mb-2 sm:mb-1">
                        <h3 class="text-lg sm:text-xl font-bold flex-grow pr-2 line-clamp-2">
                            <a :href="`product_details.php?id=${product.id}`"
                               class="hover:text-orange-600 transition-colors"
                               x-text="product.name_en"></a>
                        </h3>
                        <?php if (is_user_logged_in()): ?>
                        <button @click.stop="toggleFavorite(product.id, $data)"
                                class="p-1 rounded-full hover:bg-gray-100 transition-colors"
                                :aria-label="isFavorite ? 'Remove favorite' : 'Add to favorites'">
                            <i :class="isFavorite ? 'fas text-red-500' : 'far text-gray-400'" 
                               class="fa-heart text-lg"></i>
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center text-xs sm:text-sm text-gray-500 mb-3 sm:mb-4">
                        <template x-if="product.avg_rating > 0">
                            <div class="flex">
                                <span class="text-yellow-400 mr-1" 
                                      x-html="'★'.repeat(Math.round(product.avg_rating)) + '★'.repeat(5 - Math.round(product.avg_rating)).replace(/★/g, '<span class=&quot;text-gray-300&quot;>★</span>')"></span>
                                <span class="text-gray-400 ml-1" 
                                      x-text="`(${parseFloat(product.avg_rating).toFixed(1)})`"></span>
                            </div>
                        </template>
                        <template x-if="!product.avg_rating || product.avg_rating == 0">
                            <span class="text-gray-400">No reviews yet</span>
                        </template>
                    </div>

                    <div class="flex justify-between items-center mt-auto pt-3 border-t border-gray-100">
                        <div class="flex flex-col">
                            <template x-if="product.discount_percentage > 0">
                                <span class="text-xs text-gray-400 line-through" x-text="`${product.price} Ks`"></span>
                            </template>
                            <span class="text-xl sm:text-2xl font-bold text-orange-600" 
                                  x-text="`${product.discount_percentage > 0 ? (product.price - (product.price * product.discount_percentage / 100)) : product.price} Ks` "></span>
                        </div>
                        <button @click="addToCart(product.id, $data)"
                                :disabled="busy"
                                class="btn-brand p-2 sm:p-3 h-10 sm:h-12 w-10 sm:w-12 rounded-full flex items-center justify-center ml-2 transition-all hover:scale-105">
                            <i :class="busy ? 'fas fa-spinner fa-spin' : 'fas fa-shopping-cart'" 
                               class="text-base sm:text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
<?php endforeach; ?>


<script>
function toggleFavorite(productId, alpineComponent) {
    fetch('/api/toggle_favorite.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ product_id: productId })})
    .then(res => res.json()).then(data => { if (data.status === 'success') { alpineComponent.isFavorite = (data.action === 'added'); }});
}
function addToCart(productId, alpineComponent) {
    alpineComponent.busy = true;
    fetch('/api/cart_handler.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'add', product_id: productId })})
    .then(response => response.json()).then(data => {
        if (data.status === 'success') {
            Toastify({ text: "Added to cart!", duration: 3000, style: { background: "linear-gradient(to right, #00b09b, #96c93d)" } }).showToast();
            updateCartCounts(data.cart_count);
        } else { Toastify({ text: "Failed to add item.", duration: 3000, style: { background: "linear-gradient(to right, #ff5f6d, #ffc371)" } }).showToast(); }
    }).finally(() => { setTimeout(() => { alpineComponent.busy = false; }, 300); });
}
function addComboToCart(comboId, alpineComponent) {
    alpineComponent.busy = true;
    fetch('/api/add_combo_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ combo_id: comboId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Toastify({ text: "Combo added to cart!", duration: 3000, style: { background: "linear-gradient(to right, #00b09b, #96c93d)" } }).showToast();
            // FIX: Use the total quantity returned from the API
            updateCartCounts(data.cart_count); 
        } else {
            Toastify({ text: "Failed to add combo: " + (data.message || 'Unknown error'), duration: 3000, style: { background: "linear-gradient(to right, #ff5f6d, #ffc371)" } }).showToast();
        }
    })
    .finally(() => {
        setTimeout(() => { alpineComponent.busy = false; }, 300);
    });
}

function updateCartCounts(count) {
    document.getElementById('cart-count').textContent = count;
    const mobileCount = document.getElementById('mobile-cart-count');
    if (mobileCount) {
        mobileCount.textContent = count;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
