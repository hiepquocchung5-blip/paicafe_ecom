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
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Compile and generate custom prompts for Local LLMs using product specifications, images, and videos.</p>
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
            <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2.5rem] border border-slate-200 dark:border-slate-800 p-6 shadow-xl">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6">Product Catalog</h3>
                
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
                                <span class="text-[8px] font-bold text-slate-400 mt-1 uppercase" x-text="prod.video_url ? '📷 + 🎥 Media' : '📷 Photo Only'"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Right Side: Prompt Configuration & Output -->
        <div class="lg:col-span-5 space-y-6">
            <!-- Configuration Box -->
            <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2.5rem] border border-slate-200 dark:border-slate-800 p-8 shadow-xl">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6">Prompt Controls</h3>
                
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

                    <!-- Custom Instructions -->
                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Custom LLM Instructions</label>
                        <textarea x-model="customInstructions" placeholder="e.g. Include address, tell users to scan QR code, mention discount specials..." rows="3"
                                  class="w-full bg-slate-100 dark:bg-slate-950 border-none rounded-xl px-4 py-3 text-xs text-slate-800 dark:text-white font-bold focus:ring-2 focus:ring-orange-500/50 transition-all"></textarea>
                    </div>
                </div>
            </div>

            <!-- Output Prompt Result Box -->
            <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2.5rem] border border-slate-200 dark:border-slate-800 p-8 shadow-xl relative">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Generated Prompt</h3>
                    <span class="text-[9px] font-mono text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded" x-text="computedPrompt.length + ' chars'"></span>
                </div>

                <!-- Prompt Display Area -->
                <div class="bg-slate-100 dark:bg-slate-950 rounded-2xl p-4 text-xs font-mono max-h-[300px] overflow-y-auto whitespace-pre-wrap select-all text-slate-700 dark:text-slate-300 leading-relaxed border border-slate-200 dark:border-slate-800"
                     x-text="computedPrompt">
                </div>

                <!-- Copy / Download Buttons -->
                <div class="flex gap-4 mt-6">
                    <button @click="copyPrompt()" 
                            class="flex-1 bg-orange-600 hover:bg-orange-500 text-white py-3.5 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all shadow-lg shadow-orange-600/20 flex items-center justify-center space-x-2">
                        <i class="fas" :class="copying ? 'fa-check' : 'fa-copy'"></i>
                        <span x-text="copying ? 'Copied!' : 'Copy Prompt'"></span>
                    </button>
                    <button @click="downloadPrompt()"
                            class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-6 py-3.5 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all flex items-center justify-center space-x-2">
                        <i class="fas fa-download"></i>
                        <span>Download</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function promptGeneratorState() {
    const rawProducts = <?= json_encode($products, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    
    // Extract unique categories for filtering
    const categoriesSet = new Set();
    rawProducts.forEach(p => {
        if (p.category_name) categoriesSet.add(p.category_name);
    });
    
    return {
        products: rawProducts,
        categories: Array.from(categoriesSet),
        selectedProducts: [],
        activeCategory: 'ALL',
        
        // Form Controls
        promptType: 'Social Media Advertisement',
        tone: 'Luxurious and Premium',
        language: 'Bilingual (English with Burmese translation)',
        customInstructions: '',
        
        // Copy UI Feedback state
        copying: false,
        appName: 'PAICAFE Lounge & Cafe',
        
        init() {
            // Watch settings changes and auto compile
        },
        
        formatCurrency(amount) {
            return parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 0 });
        },
        
        filteredProducts() {
            if (this.activeCategory === 'ALL') {
                return this.products;
            }
            return this.products.filter(p => p.category_name === this.activeCategory);
        },
        
        selectAll() {
            this.selectedProducts = this.filteredProducts().map(p => p.id);
        },
        
        deselectAll() {
            this.selectedProducts = [];
        },
        
        get computedPrompt() {
            if (this.selectedProducts.length === 0) {
                return `[LLM PROMPT ENGINE SYSTEM INITIALIZATION]
Status: Ready
Action: Please select one or more products from the catalog grid to compile a local LLM prompt matrix.`;
            }
            
            let selectedData = this.products.filter(p => this.selectedProducts.includes(p.id));
            let prompt = `System Instructions:\nYou are an advanced marketing assistant for ${this.appName}.
Create a highly-optimized, structured, and compelling ${this.promptType} for the items listed below.
The target tone should be "${this.tone}" and the language format must be "${this.language}".
`;
            if (this.customInstructions.trim()) {
                prompt += `Special Directives:\n- ${this.customInstructions.trim()}\n`;
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
            prompt += `1. Draft an engaging promotional headline.\n`;
            prompt += `2. Write detailed, appetizing sales copy emphasizing product characteristics, presentation, images, and videos.\n`;
            prompt += `3. Include local-offline optimization cues for a LocalLLM (no external API lookup needed).\n`;
            prompt += `4. Add call-to-actions, QR scanning notes, and relevant hashtags (#paicafe #payvia).`;
            
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
