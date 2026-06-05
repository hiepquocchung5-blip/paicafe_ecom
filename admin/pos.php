<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

// Fetch categories for the filter buttons
$categories_stmt = $pdo->query("SELECT name_en FROM categories ORDER BY name_en ASC");
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);
// Fetch tax rate
$tax_rate = get_setting($pdo, 'tax_percentage', 5) / 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Paicafe POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .pos-scroll::-webkit-scrollbar { width: 5px; }
        .pos-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .pos-scroll::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
        #rotate-overlay { display: none; position: fixed; inset: 0; background-color: #1a202c; color: white; z-index: 100; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
        #rotate-overlay i { font-size: 5rem; margin-bottom: 1rem; animation: rotate-anim 2.5s ease-in-out infinite; }
        @keyframes rotate-anim { 0% { transform: rotate(0deg); } 40% { transform: rotate(90deg); } 60% { transform: rotate(90deg); } 100% { transform: rotate(0deg); } }
        @media (max-width: 768px) and (orientation: portrait) { #rotate-overlay { display: flex; } .pos-container { display: none; } }
        @media print { body > *:not(.voucher-print-area) { display: none !important; } .no-print { display: none !important; } .voucher-print-area { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none !important; border: none !important; display: block; visibility: visible; } }
    </style>
</head>
<body class="bg-gray-200" x-data="posSystem(<?= htmlspecialchars(json_encode($categories)) ?>, <?= $tax_rate ?>)">
    
    <div id="rotate-overlay">
        <i class="fas fa-mobile-alt"></i>
        <h2 class="text-2xl font-bold">Please Rotate Your Device</h2>
        <p class="mt-2 text-lg">The POS interface is best viewed in landscape mode.</p>
    </div>

    <div class="pos-container flex flex-col md:flex-row h-screen font-sans">
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-md p-2 flex justify-between items-center no-print">
                <h1 class="text-xl font-bold text-gray-800">Point of Sale</h1>
                <div class="flex items-center space-x-2">
                    <input type="text" x-model.debounce.300ms="searchTerm" placeholder="Search..." class="form-input w-40 sm:w-64 py-2">
                    <a href="/index.php" class="text-gray-600 hover:text-orange-600" title="Dashboard"><i class="fas fa-tachometer-alt fa-lg"></i></a>
                </div>
            </header>
            <div class="bg-white p-2 border-y no-print">
                <div class="flex space-x-2 overflow-x-auto pb-1">
                    <button @click="selectedCategory = 'All'" :class="{ 'bg-orange-500 text-white': selectedCategory === 'All' }" class="px-3 py-1 text-sm rounded-lg font-semibold flex-shrink-0">All</button>
                    <template x-for="category in categories"><button @click="selectedCategory = category" :class="{ 'bg-orange-500 text-white': selectedCategory === category }" x-text="category" class="px-3 py-1 text-sm rounded-lg font-semibold flex-shrink-0"></button></template>
                </div>
            </div>
            <main class="flex-1 p-2 md:p-4 overflow-y-auto pos-scroll">
                <div x-show="loading" class="text-center py-10 text-gray-500">Loading products...</div>
                <div x-show="!loading && filteredProducts.length === 0" class="text-center py-10 text-gray-500">No products found.</div>
                <div x-show="!loading" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 md:gap-4">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <div @click="addToCart(product)" class="bg-white rounded-lg shadow-sm hover:shadow-lg transition-shadow cursor-pointer p-2 flex flex-col text-center relative overflow-hidden">
                            <template x-if="product.discount_percentage > 0">
                                <div class="absolute top-0 right-0 bg-red-500 text-white text-[10px] px-1 py-0.5 rounded-bl-lg font-bold">
                                    -<span x-text="parseFloat(product.discount_percentage)"></span>%
                                </div>
                            </template>
                            <img :src="product.image || '/assets/uploads/placeholder.png'" class="w-full h-16 md:h-24 object-cover rounded-md mb-2">
                            <h3 class="font-semibold text-gray-700 flex-grow text-xs md:text-sm" x-text="product.name_en"></h3>
                            <div class="flex flex-col items-center">
                                <template x-if="product.discount_percentage > 0">
                                    <span class="text-[10px] text-gray-400 line-through" x-text="formatCurrency(product.price)"></span>
                                </template>
                                <p class="text-blue-600 font-bold text-sm md:text-base" x-text="formatCurrency(product.price - (product.price * product.discount_percentage / 100))"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </main>
        </div>

        <div class="w-full md:w-96 bg-white shadow-2xl flex flex-col">
            <div class="flex-1 p-4 pos-scroll overflow-y-auto">
                <h2 class="text-xl font-bold border-b pb-2 mb-4">Current Order</h2>
                <div x-show="Object.keys(cart).length === 0" class="text-gray-500 text-center py-10"><p>Click on a product to add it.</p></div>
                <div class="space-y-3">
                    <template x-for="(item, productId) in cart" :key="productId">
                        <div class="flex items-center"><div class="flex-grow pr-2"><p class="font-semibold" x-text="item.name"></p><p class="text-sm text-gray-500" x-text="formatCurrency(item.price)"></p></div><div class="flex items-center"><button @click="updateQuantity(productId, -1)" class="px-2 py-1 bg-gray-200 rounded-l">-</button><input type="text" :value="item.quantity" @change="manualUpdateQuantity(productId, $event.target.value)" class="w-12 text-center border-t border-b"><button @click="updateQuantity(productId, 1)" class="px-2 py-1 bg-gray-200 rounded-r">+</button></div><p class="w-20 text-right font-semibold" x-text="formatCurrency(item.price * item.quantity)"></p><button @click="removeFromCart(productId)" class="ml-3 text-red-500 hover:text-red-700"><i class="fas fa-trash-alt"></i></button></div>
                    </template>
                </div>
            </div>
            <div class="p-4 border-t bg-gray-50 no-print">
                <div class="space-y-1 text-lg">
                    <div class="flex justify-between"><span>Subtotal</span><span x-text="formatCurrency(totals.subtotal)"></span></div>
                    <div x-show="couponDiscount > 0" class="flex justify-between text-red-500">
                        <span>Discount (<span x-text="couponCode"></span>)</span>
                        <span x-text="'-' + formatCurrency(couponDiscount)"></span>
                    </div>
                    <div class="flex justify-between"><span>Tax (<span x-text="taxRate * 100"></span>%)</span><span x-text="formatCurrency(totals.tax)"></span></div>
                </div>
                <div class="flex justify-between items-center text-2xl font-bold text-orange-600 border-t-2 pt-2 mt-2">
                    <span>Total</span>
                    <span x-text="formatCurrency(totals.final)"></span>
                </div>
                <button @click="openPaymentModal = true" :disabled="Object.keys(cart).length === 0" class="w-full mt-4 btn-brand py-4 text-xl"><i class="fas fa-credit-card mr-2"></i> Charge</button>
            </div>
        </div>
    </div>
    
    <div x-show="openPaymentModal" @keydown.escape.window="openPaymentModal = false" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-40 p-4 no-print" style="display: none;">
        <div @click.away="openPaymentModal = false" class="bg-white rounded-lg p-6 w-full max-w-lg max-h-[90vh] flex flex-col">
            <h2 class="text-2xl font-bold mb-4">Complete Payment</h2>
            <div class="overflow-y-auto space-y-4 pr-2">
                <p class="text-5xl text-center font-bold" x-text="formatCurrency(totals.final)"></p>
                <div class="mb-4">
                    <label class="block font-semibold">Coupon Code</label>
                    <div class="flex space-x-2"><input type="text" x-model="couponCode" placeholder="Optional" class="form-input flex-grow"><button type="button" @click="applyCoupon()" class="btn-secondary flex-shrink-0">Apply</button></div>
                    <p x-text="couponMessage" class="text-sm mt-1" :class="couponDiscount > 0 ? 'text-green-600' : 'text-red-600'"></p>
                </div>
                <div><label class="block font-semibold">Payment Method</label><select x-model="paymentMethod" @change="amountTendered = null" class="form-input mt-1 bg-white"><option value="Cash">Cash</option><option value="Online">Online</option></select></div>
                <div x-show="paymentMethod === 'Cash'" class="space-y-4">
                    <div><label class="block font-semibold">Amount Tendered</label><input type="number" x-model.number="amountTendered" class="form-input mt-1 text-lg"></div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2"><button @click="amountTendered = 5000" class="py-2 bg-gray-200 rounded">5,000</button><button @click="amountTendered = 10000" class="py-2 bg-gray-200 rounded">10,000</button><button @click="amountTendered = 20000" class="py-2 bg-gray-200 rounded">20,000</button><button @click="amountTendered = totals.final" class="py-2 bg-gray-200 rounded">Exact</button></div>
                    <div x-show="amountTendered >= totals.final" class="text-center bg-blue-100 p-3 rounded-lg"><span class="text-lg font-semibold text-blue-800">Change Due: </span><span class="text-2xl font-bold text-blue-800" x-text="formatCurrency(amountTendered - totals.final)"></span></div>
                </div>
                
                <div>
                    <label class="block font-semibold">Customer Phone (for points)</label>
                    <div class="flex space-x-2">
                        <input type="tel" x-model="customerPhone" placeholder="Optional" class="form-input flex-grow">
                        <button type="button" @click="findCustomer()" class="btn-secondary flex-shrink-0">Find</button>
                    </div>
                    <p x-text="customerMessage" class="text-sm mt-1" :class="{
                        'text-green-600': customerName,
                        'text-gray-500': !customerName
                    }"></p>
                </div>
                </div>
            <div class="mt-auto pt-4 border-t flex justify-end space-x-4">
                <button @click="openPaymentModal = false" class="btn-outline py-2 px-6">Cancel</button>
                <button @click="submitOrder()" class="btn-brand py-2 px-6" :disabled="processing"><span x-show="!processing">Confirm</span><span x-show="processing"><i class="fas fa-spinner fa-spin"></i></span></button>
            </div>
        </div>
    </div>
    
    <div x-show="showReceiptModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg w-full max-w-sm flex flex-col">
            <div class="voucher-print-area bg-gradient-to-br from-orange-50 to-amber-100 p-6 rounded-t-lg">
                <div class="text-center mb-6"><h2 class="text-2xl font-bold text-orange-900">POS Receipt</h2><p class="text-sm text-orange-800">Order #<span x-text="receiptDetails.orderId"></span></p></div>
                <div class="border-t border-b border-dashed border-orange-300 py-2 space-y-1 text-orange-900">
                    <template x-for="item in receiptDetails.cart"><div class="flex justify-between text-sm"><span x-text="`${item.quantity}x ${item.name}`"></span><span x-text="formatCurrency(item.price * item.quantity)"></span></div></template>
                </div>
                <div class="border-t-2 border-orange-300 pt-3 mt-3 space-y-1 text-orange-900">
                    <div class="flex justify-between font-semibold"><p>Subtotal</p><p x-text="formatCurrency(receiptDetails.totals.subtotal)"></p></div>
                    <template x-if="receiptDetails.couponDiscount > 0"><div class="flex justify-between font-semibold text-red-500"><p>Discount (<span x-text="receiptDetails.couponCode"></span>)</p><p x-text="'-' + formatCurrency(receiptDetails.couponDiscount)"></p></div></template>
                    <div class="flex justify-between font-semibold"><p>Tax</p><p x-text="formatCurrency(receiptDetails.totals.tax)"></p></div>
                    <div class="flex justify-between text-xl font-bold mt-2"><p>Total</p><p x-text="formatCurrency(receiptDetails.totals.final)"></p></div>
                </div>
                <p class="text-center text-xs text-orange-800 opacity-75 mt-4">Thank you for your purchase!</p>
            </div>
            <div class="p-4 bg-gray-100 flex justify-between rounded-b-lg no-print">
                <button @click="printReceipt()" class="btn-outline py-2 px-4 text-sm">Print Receipt</button>
                <button @click="startNewOrder()" class="btn-brand py-2 px-4 text-sm">New Order</button>
            </div>
        </div>
    </div>
<script>
    function posSystem(categories, taxRate) {
        return {
            loading: true, products: [], categories: categories, selectedCategory: 'All', searchTerm: '', cart: {}, 
            taxRate: taxRate, // Use the tax rate from PHP
            openPaymentModal: false, paymentMethod: 'Cash', customerPhone: '', processing: false, amountTendered: null,
            
            // NEW: Added state for customer lookup
            customerName: '', customerPoints: 0, customerMessage: '',
            
            showReceiptModal: false, receiptDetails: { 
                orderId: null, 
                cart: [], 
                totals: { subtotal: 0, tax: 0, final: 0 } 
            }, 
            couponCode: '', couponDiscount: 0, couponMessage: '',
            
            init() { this.fetchProducts(); },
            
            fetchProducts() { 
                fetch('api/get_products.php') // This now points to /admin/api/get_products.php
                    .then(res => {
                        if (!res.ok) { throw new Error('Network response was not ok'); }
                        return res.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            this.products = data.products;
                        } else {
                            alert('Error loading products: ' + (data.message || 'Unknown error'));
                        }
                        this.loading = false; 
                    })
                    .catch(err => {
                        console.error('Fetch Error:', err);
                        alert('Failed to fetch products. Check API path and logs.');
                        this.loading = false;
                    });
            },
            
            get filteredProducts() {
                let f = this.products;
                if (this.selectedCategory !== 'All') { f = f.filter(p => p.category_name === this.selectedCategory); }
                if (this.searchTerm.trim() !== '') { f = f.filter(p => p.name_en.toLowerCase().includes(this.searchTerm.toLowerCase())); }
                return f;
            },
            
            addToCart(p) { 
                const finalPrice = p.price - (p.price * (p.discount_percentage || 0) / 100);
                if(this.cart[p.id]){this.cart[p.id].quantity++;}else{this.cart[p.id]={name:p.name_en,price:parseFloat(finalPrice),quantity:1, image: p.image};}
            },
            updateQuantity(id, amt) { if(this.cart[id]){this.cart[id].quantity+=amt; if(this.cart[id].quantity<=0){delete this.cart[id];}}},
            manualUpdateQuantity(id, val) { const q = parseInt(val); if(!isNaN(q) && q > 0){this.cart[id].quantity=q;}else{delete this.cart[id];}},
            removeFromCart(id) { delete this.cart[id]; },
            
            get totals() {
                let subtotal = 0;
                for (let id in this.cart) { subtotal += this.cart[id].price * this.cart[id].quantity; }
                const taxableAmount = subtotal - this.couponDiscount;
                const tax = taxableAmount < 0 ? 0 : taxableAmount * this.taxRate;
                const final = taxableAmount + tax;
                return { subtotal, tax, final };
            },
            
            formatCurrency(amt) { return new Intl.NumberFormat('en-US',{style:'currency',currency:'MMK', minimumFractionDigits: 0}).format(amt || 0);},

            applyCoupon() {
                this.couponMessage = 'Applying...';
                fetch('api/apply_coupon.php', { 
                    method: 'POST', headers: {'Content-Type':'application/json'}, 
                    body: JSON.stringify({ code: this.couponCode, subtotal: this.totals.subtotal })
                })
                .then(res => res.json())
                .then(data => {
                    this.couponMessage = data.message;
                    if (data.status === 'success') { this.couponDiscount = data.discount; } 
                    else { this.couponDiscount = 0; }
                });
            },

            // NEW: Function to find customer by phone
            findCustomer() {
                if (!this.customerPhone.trim()) {
                    this.customerMessage = 'Please enter a phone number.';
                    return;
                }
                this.customerMessage = 'Checking...';
                this.customerName = '';
                this.customerPoints = 0;

                fetch(`api/get_user_by_phone.php?phone=${encodeURIComponent(this.customerPhone)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            this.customerName = data.user.username;
                            this.customerPoints = data.user.loyalty_points;
                            this.customerMessage = `Customer: ${this.customerName} (${this.customerPoints} pts)`;
                        } else if (data.status === 'not_found') {
                            this.customerMessage = 'New customer.';
                        } else {
                            this.customerMessage = data.message || 'Error checking customer.';
                        }
                    })
                    .catch(err => {
                        console.error('Find Customer Error:', err);
                        this.customerMessage = 'Failed to connect to server.';
                    });
            },

            submitOrder() {
                this.processing = true;
                fetch('api/submit_pos_order.php', { 
                    method: 'POST', headers: {'Content-Type':'application/json'}, 
                    body: JSON.stringify({ 
                        cart: this.cart, 
                        payment_method: this.paymentMethod, 
                        customer_phone: this.customerPhone,
                        coupon_code: this.couponCode,
                        discount_amount: this.couponDiscount
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        this.receiptDetails = { 
                            orderId: data.order_id, cart: Object.values(this.cart), 
                            totals: this.totals, couponCode: this.couponCode,
                            couponDiscount: this.couponDiscount
                        };
                        this.showReceiptModal = true;
                        this.resetOrder();
                    } else { alert('Error: ' + data.message); }
                })
                .finally(() => { this.processing = false; this.openPaymentModal = false; });
            },

            resetOrder() { 
                this.cart={}; this.paymentMethod='Cash'; this.customerPhone=''; this.amountTendered=null; 
                this.couponCode=''; this.couponDiscount=0; this.couponMessage=''; 
                // NEW: Reset customer data
                this.customerName=''; this.customerPoints=0; this.customerMessage=''; 
            },

            printReceipt() {
                if (this.receiptDetails.orderId) {
                    const url = `print_receipt.php?order_id=${this.receiptDetails.orderId}`;
                    window.open(url, '_blank', 'width=400,height=700');
                }
            },
            
            startNewOrder() { this.showReceiptModal = false; this.receiptDetails = {}; }
        }
    }
</script>

</body>
</html>