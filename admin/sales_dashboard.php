<?php include __DIR__ . '/partials/header.php'; ?>

<!-- Include the Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.sales-hero{background:linear-gradient(135deg,#7c2d12,#c2410c 55%,#ea580c);box-shadow:0 24px 60px rgba(124,45,18,.2)}
.sales-panel{border:1px solid var(--border-light);border-radius:24px;background:var(--surface-card);box-shadow:0 14px 38px rgba(42,31,23,.07)}
.sales-chart{position:relative;height:300px}.sales-chart--small{height:260px}
@media(max-width:640px){.sales-chart{height:240px}.sales-chart--small{height:220px}}
</style>
<div class="container mx-auto px-2 md:px-4" x-data="salesDashboard()">
    <div x-show="error" x-cloak class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="error"></div>
    <header class="sales-hero rounded-[2rem] p-7 md:p-9 mb-7 text-white"><p class="text-xs font-black uppercase tracking-[.22em] text-orange-100">Performance overview</p><h1 class="text-3xl md:text-5xl font-black mt-2 tracking-tight">Sales analytics</h1><p class="mt-2 text-orange-100">Understand revenue, order patterns and your most popular menu items.</p></header>

    <!-- Date Range Picker -->
    <div class="sales-panel p-5 mb-6 flex flex-col sm:flex-row sm:items-end gap-4">
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
            <input type="date" x-model="startDate" class="form-input">
        </div>
        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
            <input type="date" x-model="endDate" class="form-input">
        </div>
        <button @click="fetchData()" :disabled="loading" class="btn-brand sm:ml-auto"><span x-show="!loading"><i class="fas fa-chart-line mr-2"></i>Update report</span><span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Loading</span></button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <article class="sales-panel p-5"><p class="text-xs font-bold text-gray-500">Revenue</p><strong class="block mt-2 text-2xl md:text-3xl text-emerald-600" x-text="money(summary.revenue)"></strong></article>
        <article class="sales-panel p-5"><p class="text-xs font-bold text-gray-500">Completed orders</p><strong class="block mt-2 text-3xl text-orange-600" x-text="summary.order_count || 0"></strong></article>
        <article class="sales-panel p-5"><p class="text-xs font-bold text-gray-500">Average order value</p><strong class="block mt-2 text-2xl md:text-3xl text-purple-600" x-text="money(summary.average_order)"></strong></article>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Sales Chart -->
        <div class="lg:col-span-2 sales-panel p-6">
            <h2 class="text-2xl font-bold mb-4">Revenue Over Time</h2>
            <div class="sales-chart"><canvas id="salesChart"></canvas></div>
        </div>
        <!-- Order Type Chart -->
        <div class="sales-panel p-6">
            <h2 class="text-2xl font-bold mb-4">Order Types</h2>
            <div class="sales-chart--small"><canvas id="orderTypeChart"></canvas></div>
        </div>
        <!-- Top Products Chart -->
        <div class="lg:col-span-3 sales-panel p-6">
            <h2 class="text-2xl font-bold mb-4">Top 5 Selling Products</h2>
            <div class="sales-chart"><canvas id="topProductsChart"></canvas></div>
        </div>
    </div>
</div>

<script>
function salesDashboard() {
    return {
        startDate: '<?= date('Y-m-d', strtotime('-30 days')) ?>',
        endDate: '<?= date('Y-m-d') ?>',
        salesChart: null,
        orderTypeChart: null,
        topProductsChart: null,
        loading: false,
        error: '',
        summary: { revenue: 0, order_count: 0, average_order: 0 },
        money(value) { return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Number(value || 0)) + ' Ks'; },
        init() {
            this.fetchData();
        },
        fetchData() {
            const url = `<?= e($admin_asset_base) ?>/api/get_sales_data.php?start_date=${encodeURIComponent(this.startDate)}&end_date=${encodeURIComponent(this.endDate)}`;
            this.loading = true;
            this.error = '';
            fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(async res => {
                    const contentType = res.headers.get('content-type') || '';
                    if (!contentType.includes('application/json')) throw new Error(`The report endpoint returned ${res.status} instead of JSON.`);
                    const data = await res.json();
                    if (!res.ok || data.status === 'error') throw new Error(data.message || 'Sales report request failed.');
                    return data;
                })
                .then(data => {
                    this.summary = data.summary || this.summary;
                    this.renderSalesChart(data.sales_by_day);
                    this.renderOrderTypeChart(data.order_types);
                    this.renderTopProductsChart(data.top_products);
                })
                .catch(error => { this.error = error.message; console.error('Sales dashboard:', error); })
                .finally(() => { this.loading = false; });
        },
        renderSalesChart(data) {
            const ctx = document.getElementById('salesChart').getContext('2d');
            if (this.salesChart) this.salesChart.destroy();
            this.salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.date),
                    datasets: [{
                        label: 'Revenue (Ks)',
                        data: data.map(d => d.total),
                        borderColor: '#EA580C',
                        backgroundColor: 'rgba(234, 88, 12, 0.1)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        borderWidth: 3
                    }]
                },
                options: { responsive:true, maintainAspectRatio:false, interaction:{intersect:false,mode:'index'}, plugins:{legend:{display:false},tooltip:{callbacks:{label:ctx=>this.money(ctx.raw)}}}, scales:{x:{grid:{display:false}},y:{beginAtZero:true}} }
            });
        },
        renderOrderTypeChart(data) {
            const ctx = document.getElementById('orderTypeChart').getContext('2d');
            if (this.orderTypeChart) this.orderTypeChart.destroy();
            this.orderTypeChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.map(d => d.order_type.toUpperCase()),
                    datasets: [{
                        data: data.map(d => d.order_count),
                        backgroundColor: ['#2563EB', '#F59E0B', '#10B981'],
                    }]
                },
                options: { responsive:true, maintainAspectRatio:false, cutout:'66%', plugins:{legend:{position:'bottom',labels:{usePointStyle:true,boxWidth:10}}} }
            });
        },
        renderTopProductsChart(data) {
            const ctx = document.getElementById('topProductsChart').getContext('2d');
            if (this.topProductsChart) this.topProductsChart.destroy();
            this.topProductsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.name_en),
                    datasets: [{
                        label: 'Units Sold',
                        data: data.map(d => d.total_sold),
                        backgroundColor: 'rgba(234, 88, 12, 0.7)',
                    }]
                },
                options: { indexAxis: 'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:{beginAtZero:true,grid:{display:false}},y:{grid:{display:false}}} }
            });
        }
    }
}
</script>
<?php include 'partials/footer.php'; ?>
