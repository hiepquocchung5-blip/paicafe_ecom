<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
include __DIR__ . '/partials/header.php';

// --- Permission Check ---
if (!has_permission('manage_products')) {
    die('Access Denied. You do not have permission to access the Prompt Engine.');
}

// --- Self-Migration for video_url column ---
try {
    $pdo->query("SELECT video_url FROM products LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->exec("ALTER TABLE products ADD video_url VARCHAR(255) DEFAULT NULL");
    } catch (Exception $ex) {
        // Silent fallback
    }
}

// Fetch all products and categories
$sql = "
    SELECT p.*, c.name_en as category_name
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
";
$products = $pdo->query($sql)->fetchAll();

// Fetch selected review if review_id is provided in GET for response prompt generation
$selected_review = null;
if (isset($_GET['review_id'])) {
    $review_id = (int)$_GET['review_id'];
    $stmt_rev = $pdo->prepare("
        SELECT r.*, p.name_en AS product_name, u.username AS user_name
        FROM reviews r
        LEFT JOIN products p ON r.product_id = p.id
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.id = ?
    ");
    $stmt_rev->execute([$review_id]);
    $selected_review = $stmt_rev->fetch();
}
?>

<div class="max-w-7xl mx-auto px-6 py-4" x-data="promptGeneratorState()">
    <!-- Header Block -->
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-orange-600 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.4em] text-slate-500 dark:text-slate-400">Offline Intelligence</h2>
            </div>
            <h1 class="text-5xl font-black text-slate-800 dark:text-white tracking-tighter leading-none">LLM Prompt Engine</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Compile brand-locked Local LLM prompts for PAICAFE products, images, videos, and campaign assets.</p>
        </div>

        <div class="flex items-center space-x-4">
            <button @click="selectAll()" 
                    class="bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all">
                Select All
            </button>
            <button @click="deselectAll()" 
                    class="bg-red-500/10 hover:bg-red-500/20 text-red-500 px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all">
                Clear Selection
            </button>
        </div>
    </div>

    <!-- Main Workspace Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left Side: Products Selection Grid -->
        <div class="lg:col-span-7 space-y-6">
            <div class="liquid-surface rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 shadow-xl">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6">Product Catalog</h3>
                
                <!-- Real-time Catalog Search -->
                <div class="mb-4 relative">
                    <input type="text" x-model="searchQuery" placeholder="Search catalog items by name, description..." 
                           class="w-full bg-slate-100 dark:bg-slate-950 border-none rounded-2xl pl-12 pr-6 py-4 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-orange-500/50 text-slate-800 dark:text-white transition-all">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                </div>

                <!-- Category Filter for Easy Selection -->
                <div class="flex flex-wrap gap-2 mb-6">
                    <button @click="activeCategory = 'ALL'" 
                            :class="activeCategory === 'ALL' ? 'bg-orange-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all">
                        All Items
                    </button>
                    <template x-for="cat in categories" :key="cat">
                        <button @click="activeCategory = cat" 
                                :class="activeCategory === cat ? 'bg-orange-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition-all"
                                x-text="cat">
                        </button>
                    </template>
                </div>

                <!-- Products Catalog List -->
                <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2 custom-scrollbar">
                    <template x-for="prod in filteredProducts()" :key="prod.id">
                        <div class="flex items-center justify-between p-4 rounded-2xl border transition-all duration-200"
                             :class="selectedProducts.includes(prod.id) ? 'bg-orange-600/10 border-orange-500/30' : 'bg-slate-50/50 dark:bg-slate-950/20 border-slate-100 dark:border-slate-800'">
                            
                            <div class="flex items-center space-x-4 flex-1">
                                <!-- Checkbox -->
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" :value="prod.id" x-model="selectedProducts" class="rounded border-slate-300 dark:border-slate-700 text-orange-600 focus:ring-orange-500/50 w-5 h-5">
                                </label>
                                
                                <!-- Product Media: Image & Video -->
                                <div class="flex items-center space-x-2">
                                    <!-- Image Thumbnail -->
                                    <div class="w-14 h-14 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                        <img :src="prod.image ? prod.image : '/assets/uploads/placeholder.png'" class="w-full h-full object-cover">
                                    </div>
                                    
                                    <!-- Video Embed / Indicator -->
                                    <template x-if="prod.video_url">
                                        <div class="w-14 h-14 rounded-xl overflow-hidden bg-black border border-slate-200 dark:border-slate-700 relative">
                                            <video :src="prod.video_url" class="w-full h-full object-cover" muted preload="metadata"></video>
                                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                                                <i class="fas fa-play text-white text-[9px]"></i>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Metadata -->
                                <div>
                                    <h4 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-tight" x-text="prod.name_en"></h4>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span class="text-[9px] font-black uppercase text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700" x-text="prod.category_name || 'UNCLASSIFIED'"></span>
                                        <span class="text-xs font-bold text-orange-600" x-text="formatCurrency(prod.price) + ' KS'"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Indicators -->
                            <div class="flex flex-col items-end pl-4">
                                <template x-if="prod.discount_percentage > 0">
                                    <span class="text-[8px] bg-red-600 text-white px-1.5 py-0.5 rounded font-black uppercase" x-text="'-' + parseFloat(prod.discount_percentage) + '%'"></span>
                                </template>
                                <div class="flex items-center gap-1.5 mt-1 text-[8px] font-bold text-slate-400 uppercase">
                                    <i class="fas fa-image"></i>
                                    <template x-if="prod.video_url">
                                        <span class="flex items-center gap-1.5"><i class="fas fa-video"></i><span>Media Ready</span></span>
                                    </template>
                                    <template x-if="!prod.video_url">
                                        <span>Photo Only</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Right Side: Prompt Configuration & Output -->
        <div class="lg:col-span-5 space-y-6">
            <!-- Configuration Box -->
            <div class="liquid-surface rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 shadow-xl">
                <div class="flex items-center gap-4">
                    <div class="w-20 h-20 rounded-2xl bg-black border border-white/10 shadow-xl flex items-center justify-center overflow-hidden">
                        <span class="text-white text-2xl font-black tracking-tight">PAI</span>
                    </div>
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Brand Lock</p>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white leading-tight">PAICAFE Logo on Solid Black</h3>
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1">Generated image and video prompts will strongly require the clean PAI/PAICAFE mark on a solid black logo field.</p>
                    </div>
                </div>
            </div>

            <div class="liquid-surface rounded-[2rem] border border-slate-200 dark:border-slate-800 p-8 shadow-xl">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6">Prompt Controls</h3>
                
                <!-- Review Response Active Context Info -->
                <div x-show="reviewData" class="p-4 rounded-2xl bg-orange-500/10 border border-orange-500/20 text-orange-600 dark:text-orange-400 text-xs font-bold mb-6 flex items-start space-x-3" style="display: none;">
                    <i class="fas fa-info-circle mt-0.5 text-base"></i>
                    <div>
                        <p class="font-black uppercase tracking-widest text-[9px]">Active Context Loaded</p>
                        <p class="mt-1 font-semibold normal-case text-[11px]" x-text="`Replying to ${reviewData ? reviewData.user_name : ''}'s ${reviewData ? reviewData.rating : 0}★ review on '${reviewData ? reviewData.product_name : ''}'`"></p>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Prompt Objective -->
                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Prompt Objective</label>
                        <select x-model="promptType" class="w-full bg-slate-100 dark:bg-slate-950 border-none rounded-xl px-4 py-3 text-sm text-slate-800 dark:text-white font-bold focus:ring-2 focus:ring-orange-500/50 appearance-none">
                            <option value="Social Media Advertisement">Social Media Advertisement (Facebook/IG)</option>
                            <option value="Chef's Recommendation & Menu Description">Chef's Recommendation Description</option>
                            <option value="SEO Product Content & Keywords">SEO Search Description</option>
                            <option value="Marketing Launch Copy">New Asset Launch Release</option>
                            <option value="Local LLM Classification Prompt">LLM Catalog Classification</option>
                            <option value="Reply to Customer Review" x-show="reviewData">Reply to Customer Review</option>
                        </select>
                    </div>

                    <!-- Creative Asset Mode -->
                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Image / Video Prompt Mode</label>
                        <select x-model="mediaMode" class="w-full bg-slate-100 dark:bg-slate-950 border-none rounded-xl px-4 py-3 text-sm text-slate-800 dark:text-white font-bold focus:ring-2 focus:ring-orange-500/50 appearance-none">
                            <option value="Balanced Image + Video Campaign">Balanced Image + Video Campaign</option>
                            <option value="Image Generation Prompt">Image Generation Prompt</option>
                            <option value="Video Generation Prompt">Video Generation Prompt</option>
                            <option value="Product Photo Enhancement Prompt">Product Photo Enhancement Prompt</option>
                            <option value="Short Reels / TikTok Script Prompt">Short Reels / TikTok Script Prompt</option>
                        </select>
                    </div>

                    <!-- Tone of Voice -->
                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Tone of Voice</label>
                        <select x-model="tone" class="w-full bg-slate-100 dark:bg-slate-950 border-none rounded-xl px-4 py-3 text-sm text-slate-800 dark:text-white font-bold focus:ring-2 focus:ring-orange-500/50 appearance-none">
                            <option value="Luxurious and Premium">Luxurious & Premium</option>
                            <option value="Energetic and Mouth-watering">Energetic & Mouth-watering</option>
                            <option value="Friendly and Warm">Friendly & Warm</option>
                            <option value="Sleek and Minimalistic">Sleek & Minimalist</option>
                            <option value="Witty and Conversational">Witty & Conversational</option>
                        </select>
                    </div>

                    <!-- Output Language -->
                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Target Language</label>
                        <select x-model="language" class="w-full bg-slate-100 dark:bg-slate-950 border-none rounded-xl px-4 py-3 text-sm text-slate-800 dark:text-white font-bold focus:ring-2 focus:ring-orange-500/50 appearance-none">
                            <option value="English Only">English Only</option>
                            <option value="Burmese Only">Burmese Only</option>
                            <option value="Bilingual (English with Burmese translation)">Bilingual (English & Burmese)</option>
                        </select>
                    </div>

                    <!-- Output Format -->
                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">LLM Output Format</label>
                        <select x-model="outputFormat" class="w-full bg-slate-100 dark:bg-slate-950 border-none rounded-xl px-4 py-3 text-sm text-slate-800 dark:text-white font-bold focus:ring-2 focus:ring-orange-500/50 appearance-none">
                            <option value="Structured Marketing Brief">Structured Marketing Brief</option>
                            <option value="Strict JSON">Strict JSON (.json)</option>
                            <option value="JSON + Human Readable Copy">JSON + Human Readable Copy</option>
                            <option value="Clean Copy Blocks">Clean Copy Blocks</option>
                        </select>
                    </div>

                    <!-- Custom Instructions -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between gap-3">
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Custom LLM Instructions</label>
                            <button type="button" @click="insertOfficialContact()" class="text-[9px] font-black uppercase tracking-widest text-orange-600 hover:text-orange-500 transition-colors">
                                Add Contact
                            </button>
                        </div>
                        <textarea x-model="customInstructions" placeholder="e.g. Include address, tell users to scan QR code, mention discount specials..." rows="3"
                                  class="w-full bg-slate-100 dark:bg-slate-950 border-none rounded-xl px-4 py-3 text-xs text-slate-800 dark:text-white font-bold focus:ring-2 focus:ring-orange-500/50 transition-all"></textarea>
                        <p x-show="shouldIncludeOfficialContact()" class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400" style="display: none;">
                            Official PAICAFE address, phone, email, and opening hours will be auto-corrected in the prompt.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Output Prompt Result Box -->
            <div class="liquid-surface rounded-[2rem] border border-slate-200 dark:border-slate-800 p-8 shadow-xl relative">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Generated Prompt</h3>
                    <span class="text-[9px] font-mono text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded" x-text="computedPrompt.length + ' chars'"></span>
                </div>

                <!-- Prompt Display Area -->
                <div class="bg-slate-100 dark:bg-slate-950 rounded-2xl p-4 text-xs font-mono max-h-[300px] overflow-y-auto whitespace-pre-wrap select-all text-slate-700 dark:text-slate-300 leading-relaxed border border-slate-200 dark:border-slate-800"
                     x-text="computedPrompt">
                </div>

                <!-- Copy / Download Buttons -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-6">
                    <button @click="copyPrompt()" 
                            class="bg-orange-600 hover:bg-orange-500 text-white py-3.5 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all shadow-lg shadow-orange-600/20 flex items-center justify-center space-x-2">
                        <i class="fas" :class="copying ? 'fa-check' : 'fa-copy'"></i>
                        <span x-text="copying ? 'Copied!' : 'Copy Prompt'"></span>
                    </button>
                    <button @click="previewOpen = true"
                            class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-3.5 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all flex items-center justify-center space-x-2">
                        <i class="fas fa-window-maximize"></i>
                        <span>Preview</span>
                    </button>
                    <button @click="downloadPrompt()"
                            class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-4 py-3.5 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all flex items-center justify-center space-x-2">
                        <i class="fas fa-download"></i>
                        <span>Download</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- LLM Prompt Preview Modal -->
    <div x-show="previewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-md" @click="previewOpen = false"></div>
        <div class="liquid-surface relative w-full max-w-4xl max-h-[86vh] overflow-hidden rounded-[2rem] border border-white/10 shadow-2xl">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200/70 dark:border-white/10 px-6 py-4">
                <div>
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">LLM Modal</p>
                    <h3 class="text-lg font-black text-slate-800 dark:text-white">Generated Prompt Output</h3>
                </div>
                <button @click="previewOpen = false" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-900 text-slate-500 hover:text-red-500 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-0">
                <div class="border-b lg:border-b-0 lg:border-r border-slate-200/70 dark:border-white/10 p-5 space-y-3">
                    <div class="rounded-2xl bg-black p-4 text-white">
                        <p class="text-[9px] font-black uppercase tracking-widest text-white/50">Brand</p>
                        <p class="mt-1 text-2xl font-black tracking-tight">PAI</p>
                    </div>
                    <div class="text-xs font-bold text-slate-600 dark:text-slate-300 space-y-2">
                        <p><span class="text-slate-400">Format:</span> <span x-text="outputFormat"></span></p>
                        <p><span class="text-slate-400">Mode:</span> <span x-text="mediaMode"></span></p>
                        <p><span class="text-slate-400">Language:</span> <span x-text="language"></span></p>
                    </div>
                    <button @click="copyPrompt()" class="w-full bg-orange-600 hover:bg-orange-500 text-white py-3 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all">
                        Copy Modal Prompt
                    </button>
                </div>
                <div class="lg:col-span-2 p-5">
                    <pre class="max-h-[58vh] overflow-y-auto custom-scrollbar whitespace-pre-wrap rounded-2xl bg-slate-100 dark:bg-slate-950 p-5 text-xs leading-relaxed text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800" x-text="computedPrompt"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function promptGeneratorState() {
    const rawProducts = <?= json_encode($products, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const selectedReview = <?= json_encode($selected_review, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    
    // Extract unique categories for filtering
    const categoriesSet = new Set();
    rawProducts.forEach(p => {
        if (p.category_name) categoriesSet.add(p.category_name);
    });
    
    return {
        products: rawProducts,
        categories: Array.from(categoriesSet),
        selectedProducts: selectedReview ? [parseInt(selectedReview.product_id)] : [],
        activeCategory: 'ALL',
        searchQuery: '',
        
        // Form Controls
        promptType: selectedReview ? 'Reply to Customer Review' : 'Social Media Advertisement',
        mediaMode: 'Balanced Image + Video Campaign',
        outputFormat: 'Structured Marketing Brief',
        tone: 'Luxurious and Premium',
        language: 'Bilingual (English with Burmese translation)',
        customInstructions: '',
        reviewData: selectedReview,
        
        // Copy UI Feedback state
        copying: false,
        previewOpen: false,
        appName: 'PAICAFE Lounge & Cafe',
        officialContact: {
            address: 'No.88, Thantumar Main Street, Thuwunna Tsp, Yangon, Myanmar',
            phone: '+95 9 8 9 0 9 0 7 7 2 4',
            email: 'contact@paicafes.com',
            hours: 'Open Daily: 9:00 AM - 6:00 PM'
        },
        
        init() {
            // Watch settings changes and auto compile
        },
        
        formatCurrency(amount) {
            return parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 0 });
        },

        shouldIncludeOfficialContact() {
            return /\b(address|location|contact|phone|email|open|opening|hours|daily)\b/i.test(this.customInstructions);
        },

        getOfficialContactBlock() {
            return `--- OFFICIAL PAICAFE CONTACT DETAILS ---
Address: ${this.officialContact.address}
Phone: ${this.officialContact.phone}
Email: ${this.officialContact.email}
Hours: ${this.officialContact.hours}`;
        },

        insertOfficialContact() {
            const directive = 'Include the official PAICAFE address, phone, email, and opening hours.';
            if (!this.customInstructions.toLowerCase().includes('official paicafe address')) {
                this.customInstructions = this.customInstructions.trim()
                    ? `${this.customInstructions.trim()}\n${directive}`
                    : directive;
            }
        },

        getNormalizedCustomInstructions() {
            let instructions = this.customInstructions.trim();
            if (this.shouldIncludeOfficialContact()) {
                const contactDirective = `Use only this corrected official contact information:\n${this.getOfficialContactBlock()}`;
                instructions = instructions ? `${instructions}\n\n${contactDirective}` : contactDirective;
            }
            return instructions;
        },

        getBrandIdentityBlock() {
            return `--- BRAND IDENTITY LOCK ---
Brand Name: PAICAFE Lounge & Cafe
Logo Rule: Use the PAICAFE identity consistently. If a compact logo mark is needed, use "PAI" exactly. Do not write "PIA", "Pai Cafe", or any other misspelling.
Logo Placement: The logo must appear as a clean, high-contrast white PAI/PAICAFE mark on a solid black rectangular or square field.
Visual Style: premium cafe, clean liquid-glass user experience, refined reflections, sharp product visibility, uncluttered composition, modern Myanmar cafe atmosphere.
Avoid: busy backgrounds behind the logo, distorted text, extra fake brand marks, unreadable typography, over-saturated filters, low-resolution food imagery, and messy UI overlays.`;
        },

        getMediaDirectionBlock() {
            const shared = `Keep every asset suitable for PAICAFE menu, social media, and in-store QR promotion. Product media must look appetizing, clean, premium, and realistic.`;
            const modes = {
                'Image Generation Prompt': `Create an image-generation-ready prompt. Prioritize a single polished hero image, clear food/drink detail, controlled lighting, solid black logo field, and clean liquid-glass styling.`,
                'Video Generation Prompt': `Create a video-generation-ready prompt. Include camera movement, scene order, duration guidance, product close-ups, logo end card on solid black, and motion that feels premium rather than noisy.`,
                'Product Photo Enhancement Prompt': `Create a product-photo-enhancement prompt. Preserve the real product identity, improve lighting, background cleanliness, color accuracy, menu readability, and add only tasteful PAICAFE branding.`,
                'Short Reels / TikTok Script Prompt': `Create a short vertical video prompt/script. Include hook, shot list, on-screen text, product close-ups, logo end card on solid black, QR/order CTA, and concise captions.`,
                'Balanced Image + Video Campaign': `Create both image and video guidance. Include a hero image prompt, supporting product image variations, a short-form video shot list, logo end card direction, and CTA copy.`
            };
            return `--- CREATIVE ASSET MODE ---
Selected Mode: ${this.mediaMode}
${modes[this.mediaMode] || modes['Balanced Image + Video Campaign']}
${shared}`;
        },

        getOutputFormatBlock() {
            const jsonSchema = `{
  "brand": {
    "name": "PAICAFE Lounge & Cafe",
    "short_mark": "PAI",
    "logo_rule": "Use exact PAICAFE or PAI mark, white on solid black field",
    "visual_style": "premium cafe, clean liquid-glass UI, realistic food and drink detail"
  },
  "campaign": {
    "objective": "",
    "tone": "",
    "language": "",
    "headline": "",
    "subheadline": "",
    "short_caption": "",
    "long_caption": "",
    "cta": "",
    "hashtags": [],
    "platform_notes": {
      "facebook": "",
      "instagram": "",
      "in_store_qr": ""
    }
  },
  "contact": {
    "address": "${this.officialContact.address}",
    "phone": "${this.officialContact.phone}",
    "email": "${this.officialContact.email}",
    "hours": "${this.officialContact.hours}"
  },
  "assets": {
    "image": {
      "prompt": "",
      "aspect_ratio": "1:1 or 4:5",
      "composition": "",
      "lighting": "",
      "logo_placement": "white PAICAFE or PAI logo on solid black field",
      "negative_prompt": "misspelled logo, PIA, distorted text, busy logo background, low-resolution product"
    },
    "video": {
      "prompt": "",
      "duration_seconds": 8,
      "aspect_ratio": "9:16",
      "shot_list": [],
      "camera_motion": "",
      "text_overlays": [],
      "end_card": "solid black background with white PAICAFE or PAI logo"
    }
  },
  "products": [
    {
      "name_en": "",
      "name_mm": "",
      "category": "",
      "price_ks": "",
      "discount": "",
      "description": "",
      "image_reference": "",
      "video_reference": "",
      "selling_points": []
    }
  ],
  "quality_check": {
    "logo_spelling_verified": true,
    "uses_official_contact_when_requested": true,
    "no_external_lookup_required": true,
    "ready_for_local_llm": true
  }
}`;

            if (this.outputFormat === 'Strict JSON') {
                return `--- OUTPUT FORMAT ---
Return valid JSON only.
Do not wrap the answer in markdown.
Do not add commentary before or after the JSON.
Use double quotes for all JSON keys and strings.
Do not use trailing commas.
Use arrays for hashtags, shot_list, text_overlays, products, and selling_points.
Fill every useful field. If a value is unknown, use an empty string instead of null.
Use this schema:
${jsonSchema}`;
            }

            if (this.outputFormat === 'JSON + Human Readable Copy') {
                return `--- OUTPUT FORMAT ---
First return a valid JSON object using this schema. Then add the readable copy after the JSON.
The JSON must be valid, use double quotes, avoid trailing commas, and use empty strings for unknown values:
${jsonSchema}

After the JSON, add a short "Readable Copy" section with polished customer-facing text.`;
            }

            if (this.outputFormat === 'Clean Copy Blocks') {
                return `--- OUTPUT FORMAT ---
Use clean labeled sections:
Headline
Short Caption
Long Caption
Image Prompt
Video Prompt
CTA
Hashtags
Contact Details`;
            }

            return `--- OUTPUT FORMAT ---
Use a structured marketing brief with clear sections for headline, campaign copy, image prompt, video prompt, CTA, hashtags, and contact details when requested.`;
        },
        
        filteredProducts() {
            let items = this.products;
            if (this.activeCategory !== 'ALL') {
                items = items.filter(p => p.category_name === this.activeCategory);
            }
            if (this.searchQuery.trim() !== '') {
                const query = this.searchQuery.toLowerCase();
                items = items.filter(p => 
                    p.name_en.toLowerCase().includes(query) || 
                    (p.name_mm && p.name_mm.toLowerCase().includes(query)) ||
                    (p.description_en && p.description_en.toLowerCase().includes(query))
                );
            }
            return items;
        },
        
        selectAll() {
            this.selectedProducts = this.filteredProducts().map(p => p.id);
        },
        
        deselectAll() {
            this.selectedProducts = [];
        },
        
        get computedPrompt() {
            // Check if replying to customer review
            if (this.promptType === 'Reply to Customer Review' && this.reviewData) {
                let p = this.products.find(prod => prod.id == this.reviewData.product_id);
                let prompt = `System Instructions:\nYou are a customer relation officer for ${this.appName}.
Write a warm, polite, and professional reply to a customer review left on our product "${this.reviewData.product_name}".
The response tone should be "${this.tone}" and written in "${this.language}".

${this.getBrandIdentityBlock()}

--- CUSTOMER REVIEW DETAILS ---
Customer: ${this.reviewData.user_name || 'Guest User'}
Rating: ${this.reviewData.rating} out of 5 stars
Customer Comment: "${this.reviewData.comment || 'No text comment left.'}"
Submitted on: ${this.reviewData.created_at}

--- PRODUCT REFERENCE DATA ---
Product Name: ${this.reviewData.product_name}
Price: ${this.formatCurrency(p ? p.price : 0)} KS
Description: ${p ? p.description_en : 'No description'}
Image: ${p ? p.image : 'No image'}
Video: ${p ? (p.video_url || 'No video') : 'No video'}
`;
                const normalizedInstructions = this.getNormalizedCustomInstructions();
                if (normalizedInstructions) {
                    prompt += `\nSpecial Directives:\n${normalizedInstructions}\n`;
                }
                prompt += `\n${this.getOutputFormatBlock()}\n`;
                prompt += `\n--- OUTPUT REQUIREMENTS ---\n`;
                prompt += `1. Express sincere gratitude for the customer's feedback.\n`;
                prompt += `2. Address specific points raised in their comment.\n`;
                prompt += `3. Maintain a positive brand image and invite them back to PAICAFE.\n`;
                prompt += `4. Use official PAICAFE contact details exactly when address or contact info is requested.\n`;
                prompt += `5. Keep the output offline-optimization friendly (no external API calls needed).`;
                return prompt;
            }

            if (this.selectedProducts.length === 0) {
                return `[LLM PROMPT ENGINE SYSTEM INITIALIZATION]
Status: Ready
Action: Please select one or more products from the catalog grid to compile a local LLM prompt matrix.`;
            }
            
            let selectedData = this.products.filter(p => this.selectedProducts.includes(p.id));
            let prompt = `System Instructions:\nYou are an advanced marketing assistant for ${this.appName}.
Create a highly-optimized, structured, and compelling ${this.promptType} for the items listed below.
The target tone should be "${this.tone}" and the language format must be "${this.language}".
${this.getBrandIdentityBlock()}

${this.getMediaDirectionBlock()}

${this.getOutputFormatBlock()}
`;
            const normalizedInstructions = this.getNormalizedCustomInstructions();
            if (normalizedInstructions) {
                prompt += `Special Directives:\n${normalizedInstructions}\n`;
            }
            
            prompt += `\n--- SOURCE PRODUCTS DATA ---\n`;
            
            selectedData.forEach((p, idx) => {
                prompt += `\n[Product Item #${idx + 1}]\n`;
                prompt += `Name (EN): ${p.name_en}\n`;
                if (p.name_mm) prompt += `Name (MM): ${p.name_mm}\n`;
                prompt += `Price: ${this.formatCurrency(p.price)} KS\n`;
                if (parseFloat(p.discount_percentage) > 0) {
                    let discountAmount = p.price * (p.discount_percentage / 100);
                    let finalPrice = p.price - discountAmount;
                    prompt += `Discount Applied: ${parseFloat(p.discount_percentage)}% (Special Promo Price: ${this.formatCurrency(finalPrice)} KS)\n`;
                }
                if (p.category_name) prompt += `Category: ${p.category_name}\n`;
                if (p.description_en) prompt += `Description (EN): ${p.description_en}\n`;
                if (p.description_mm) prompt += `Description (MM): ${p.description_mm}\n`;
                if (p.image) prompt += `Image Reference: ${p.image}\n`;
                if (p.video_url) prompt += `Video Reference: ${p.video_url}\n`;
            });
            
            prompt += `\n--- OUTPUT REQUIREMENTS ---\n`;
            prompt += `1. Draft an engaging promotional headline that matches the selected objective.\n`;
            prompt += `2. Write detailed, appetizing sales copy emphasizing product characteristics, presentation, available image references, and available video references.\n`;
            prompt += `3. Include a dedicated Image Prompt section when the selected mode needs images, with exact composition, lighting, background, aspect ratio, and logo placement.\n`;
            prompt += `4. Include a dedicated Video Prompt section when the selected mode needs video, with duration, shot list, camera movement, pacing, text overlays, and solid-black PAICAFE logo end card.\n`;
            prompt += `5. Strongly enforce the PAICAFE/PAI brand logo rule: white PAI or PAICAFE logo, exact spelling, solid black field, no distorted text.\n`;
            prompt += `6. Include local-offline optimization cues for a LocalLLM (no external API lookup needed).\n`;
            prompt += `7. Add call-to-actions, QR scanning notes, and relevant hashtags (#paicafe #payvia).\n`;
            prompt += `8. If address, contact, phone, email, opening hours, or location are requested, use the official PAICAFE contact block exactly.`;
            
            return prompt;
        },
        
        copyPrompt() {
            navigator.clipboard.writeText(this.computedPrompt).then(() => {
                this.copying = true;
                setTimeout(() => this.copying = false, 2000);
                Toastify({
                    text: "Prompt Copied to Clipboard",
                    duration: 3000,
                    gravity: "bottom",
                    position: "right",
                    style: {
                        background: "linear-gradient(to right, #ea580c, #f97316)",
                    }
                }).showToast();
            });
        },
        
        downloadPrompt() {
            const blob = new Blob([this.computedPrompt], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `paicafe_llm_prompt_${new Date().toISOString().slice(0,10)}.txt`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    };
}
</script>

<?php include 'partials/footer.php'; ?>
