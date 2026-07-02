<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

// --- Permission Check ---
// We check for manage_products permission since reviews are directly tied to products
if (!has_permission('manage_products')) {
    die('Access Denied. You do not have permission to moderate reviews.');
}

$flash_message = $_SESSION['flash_message'] ?? null;
$flash_message_type = $_SESSION['flash_message_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

// --- Handle Delete Action ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = 'Review successfully moderated/deleted.';
        $_SESSION['flash_message_type'] = 'success';
        log_activity($pdo, "Deleted review ID #$id");
    } catch (Exception $e) {
        $_SESSION['flash_message'] = 'Moderation failed: ' . $e->getMessage();
        $_SESSION['flash_message_type'] = 'error';
    }
    header('Location: reviews.php');
    exit();
}

// --- Filtering & Search ---
$rating_filter = isset($_GET['rating']) && in_array((string)$_GET['rating'], ['1', '2', '3', '4', '5'], true) ? (string)$_GET['rating'] : '';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// --- Fetch Statistics ---
$total_reviews = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$avg_rating = $pdo->query("SELECT AVG(rating) FROM reviews")->fetchColumn() ?: 0;

$rating_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$stmt_dist = $pdo->query("SELECT rating, COUNT(*) as count FROM reviews GROUP BY rating");
while ($row = $stmt_dist->fetch()) {
    $rating_counts[(int)$row['rating']] = (int)$row['count'];
}

// --- Query Construction for Reviews ---
$where_clauses = [];
$params = [];

if ($rating_filter !== '') {
    $where_clauses[] = "r.rating = :rating";
    $params[':rating'] = (int)$rating_filter;
}

if ($search_term !== '') {
    $where_clauses[] = "(p.name_en LIKE :search OR u.username LIKE :search OR r.comment LIKE :search)";
    $params[':search'] = '%' . $search_term . '%';
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count filtered total for pagination
$sql_count = "
    SELECT COUNT(*) 
    FROM reviews r
    LEFT JOIN products p ON r.product_id = p.id
    LEFT JOIN users u ON r.user_id = u.id
    $where_sql
";
$stmt_count = $pdo->prepare($sql_count);
foreach ($params as $key => $val) {
    $stmt_count->bindValue($key, $val);
}
$stmt_count->execute();
$filtered_count = $stmt_count->fetchColumn();
$total_pages = max(1, ceil($filtered_count / $limit));
$page = min($page, $total_pages);
$offset = ($page - 1) * $limit;

// Fetch page reviews
$sql_reviews = "
    SELECT r.*, p.name_en AS product_name, p.image AS product_image, u.username AS user_name
    FROM reviews r
    LEFT JOIN products p ON r.product_id = p.id
    LEFT JOIN users u ON r.user_id = u.id
    $where_sql
    ORDER BY r.created_at DESC
    LIMIT :limit OFFSET :offset
";

$stmt_reviews = $pdo->prepare($sql_reviews);
foreach ($params as $key => $val) {
    $stmt_reviews->bindValue($key, $val);
}
$stmt_reviews->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt_reviews->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt_reviews->execute();
$reviews = $stmt_reviews->fetchAll();

include __DIR__ . '/partials/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-4">
    <!-- Header Block -->
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-orange-600 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.4em] text-slate-500 dark:text-slate-400">Social Proof & Moderation</h2>
            </div>
            <h1 class="text-5xl font-black text-slate-800 dark:text-white tracking-tighter leading-none">Customer Reviews</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Displaying and moderating feedback from PAICAFE visitors.</p>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <form method="GET" class="relative group">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-orange-500 transition-colors"></i>
                <input type="text" name="search" placeholder="Search comments, products..." value="<?= e($search_term) ?>" 
                       class="bg-white/50 dark:bg-slate-900/50 backdrop-blur-md border border-slate-200 dark:border-slate-800 rounded-2xl pl-12 pr-6 py-3 text-sm font-bold focus:outline-none focus:border-orange-500/50 w-72 transition-all">
                <?php if ($rating_filter !== ''): ?>
                    <input type="hidden" name="rating" value="<?= e($rating_filter) ?>">
                <?php endif; ?>
            </form>
            
            <div class="relative">
                <select onchange="location = this.value;" class="bg-white/50 dark:bg-slate-900/50 backdrop-blur-md border border-slate-200 dark:border-slate-800 rounded-2xl px-6 py-3 text-sm font-bold focus:outline-none focus:border-orange-500/50 appearance-none pr-10 cursor-pointer">
                    <option value="?search=<?= e($search_term) ?>" <?= $rating_filter === '' ? 'selected' : '' ?>>ALL RATINGS</option>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="?rating=<?= $i ?>&search=<?= e($search_term) ?>" <?= (string)$rating_filter === (string)$i ? 'selected' : '' ?>><?= $i ?> STARS</option>
                    <?php endfor; ?>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                    <i class="fas fa-chevron-down text-xs"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Notifications -->
    <?php if ($flash_message): ?>
        <div class="mb-8 p-5 rounded-2xl border flex items-center space-x-4 <?= $flash_message_type === 'success' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-500' : 'bg-red-500/10 border-red-500/20 text-red-500' ?>">
            <i class="fas <?= $flash_message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
            <p class="text-xs font-black uppercase tracking-widest"><?= e($flash_message) ?></p>
        </div>
    <?php endif; ?>

    <!-- Summary Stats Widgets -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
        <!-- Average Score widget -->
        <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2.5rem] border border-slate-200 dark:border-slate-800 p-8 flex flex-col justify-center items-center shadow-lg">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Average Satisfaction</h3>
            <span class="text-6xl font-black text-slate-800 dark:text-white tracking-tighter leading-none mb-4"><?= number_format($avg_rating, 1) ?></span>
            
            <div class="flex text-yellow-400 text-xl mb-2">
                <?php 
                $full_stars = floor($avg_rating);
                $half_star = ($avg_rating - $full_stars) >= 0.5;
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $full_stars) {
                        echo '<i class="fas fa-star mr-1"></i>';
                    } elseif ($i == $full_stars + 1 && $half_star) {
                        echo '<i class="fas fa-star-half-alt mr-1"></i>';
                    } else {
                        echo '<i class="far fa-star mr-1"></i>';
                    }
                }
                ?>
            </div>
            <p class="text-xs text-slate-400 font-bold uppercase mt-1">Based on <?= number_format($total_reviews) ?> Total Submissions</p>
        </div>

        <!-- Distribution chart widget -->
        <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2.5rem] border border-slate-200 dark:border-slate-800 p-8 lg:col-span-2 shadow-lg">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-6">Rating Spread Breakdown</h3>
            <div class="space-y-3">
                <?php 
                for ($star = 5; $star >= 1; $star--): 
                    $count = $rating_counts[$star];
                    $percent = $total_reviews > 0 ? ($count / $total_reviews) * 100 : 0;
                ?>
                <div class="flex items-center text-xs">
                    <span class="w-12 font-bold text-slate-500 dark:text-slate-400 flex items-center"><?= $star ?> <i class="fas fa-star text-yellow-400 ml-1.5"></i></span>
                    <div class="flex-grow mx-4 h-2 bg-slate-100 dark:bg-slate-950 rounded-full overflow-hidden">
                        <div class="h-full bg-orange-600 rounded-full transition-all duration-500" style="width: <?= $percent ?>%"></div>
                    </div>
                    <span class="w-12 text-right font-black text-slate-800 dark:text-white"><?= number_format($percent, 0) ?>%</span>
                    <span class="w-8 text-right font-semibold text-slate-400 ml-1">(<?= $count ?>)</span>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Review Management List -->
    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2.5rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl">
        <?php if (empty($reviews)): ?>
            <div class="p-20 text-center">
                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="far fa-comment-slash text-slate-400 text-xl"></i>
                </div>
                <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-wider">No reviews found</h3>
                <p class="text-xs text-slate-400 mt-2">Adjust your filtering parameters or verify active user reviews.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
                            <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em] w-48">Product Info</th>
                            <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em] w-32">Rating</th>
                            <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em] w-36">Reviewer</th>
                            <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em]">Feedback Comment</th>
                            <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em] w-40">Submitted</th>
                            <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em] w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php foreach ($reviews as $review): ?>
                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all duration-200">
                            <td class="p-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 flex-shrink-0">
                                        <img src="<?= e($review['product_image'] ?: '/assets/uploads/placeholder.png') ?>" class="w-full h-full object-cover">
                                    </div>
                                    <div>
                                        <p class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-tight line-clamp-1"><?= e($review['product_name'] ?: 'Unknown SKU') ?></p>
                                        <p class="text-[9px] text-slate-400 font-mono">ID: <?= sprintf("%05d", $review['product_id']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-6">
                                <div class="flex text-yellow-400 text-xs">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <i class="<?= $i < $review['rating'] ? 'fas' : 'far' ?> fa-star mr-0.5"></i>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td class="p-6">
                                <p class="text-xs font-black text-slate-700 dark:text-slate-300"><?= e($review['user_name'] ?: 'Guest User') ?></p>
                                <p class="text-[9px] text-slate-400 font-mono">UID: #<?= $review['user_id'] ?></p>
                            </td>
                            <td class="p-6">
                                <p class="text-xs font-semibold text-slate-600 dark:text-slate-400 leading-relaxed italic">
                                    "<?= e($review['comment'] ?: 'No text comment left.') ?>"
                                </p>
                            </td>
                            <td class="p-6">
                                <p class="text-xs font-bold text-slate-600 dark:text-slate-400"><?= date('M j, Y', strtotime($review['created_at'])) ?></p>
                                <p class="text-[9px] text-slate-400 font-mono mt-0.5"><?= date('h:i A', strtotime($review['created_at'])) ?></p>
                            </td>
                            <td class="p-6">
                                <div class="flex items-center space-x-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="prompt_generator.php?review_id=<?= e($review['id']) ?>" 
                                       class="w-8 h-8 flex items-center justify-center rounded-lg bg-orange-500/10 text-orange-500 hover:bg-orange-500 hover:text-white transition-all shadow-sm"
                                       title="Generate Response Prompt">
                                        <i class="fas fa-reply text-[10px]"></i>
                                    </a>
                                    <a href="reviews.php?action=delete&id=<?= e($review['id']) ?>" 
                                       onclick="return confirm('Moderation Alert: Confirm removal of this review from public display?');"
                                       class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-sm"
                                       title="Remove Review">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginator -->
            <?php if ($total_pages > 1): ?>
            <div class="p-8 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/30 dark:bg-slate-950/30">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Sector <?= $page ?> of <?= $total_pages ?></span>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&rating=<?= e($rating_filter) ?>&search=<?= e($search_term) ?>" 
                           class="px-5 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-orange-600 hover:text-white transition-all">PREV</a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&rating=<?= e($rating_filter) ?>&search=<?= e($search_term) ?>" 
                           class="px-5 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-orange-600 hover:text-white transition-all">NEXT</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
