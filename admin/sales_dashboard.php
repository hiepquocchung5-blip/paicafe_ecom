<?php include __DIR__ . '/partials/header.php'; ?>

<!-- Include the Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container mx-auto px-4" x-data="salesDashboard()">
    <div x-show="error" x-cloak class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="error"></div>
    <h1 class="text-3xl font-bold mb-6">Sales Analytics Dashboard</h1>

    <!-- Date Range Picker -->
    <div class="bg-white p-4 rounded-lg shadow-md mb-6 flex items-center space-x-4">
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
            <input type="date" x-model="startDate" class="form-input">
        </div>
        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
            <input type="date" x-model="endDate" class="form-input">
        </div>
        <button @click="fetchData()" :disabled="loading" class="btn-brand mt-6"><span x-show="!loading">Generate</span><span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Loading</span></button>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Sales Chart -->
        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-4">Revenue Over Time</h2>
            <canvas id="salesChart"></canvas>
        </div>
        <!-- Order Type Chart -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-4">Order Types</h2>
            <canvas id="orderTypeChart"></canvas>
        </div>
        <!-- Top Products Chart -->
        <div class="lg:col-span-3 bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-4">Top 5 Selling Products</h2>
            <canvas id="topProductsChart"></canvas>
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
                        tension: 0.1
                    }]
                }
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
                }
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
                options: { indexAxis: 'y' }
            });
        }
    }
}
</script>
<?php include 'partials/footer.php'; ?>
