<?php
require_once __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/partials/header.php';

// Fetch all unique floor names to create the tabs
$floors = $pdo->query("SELECT DISTINCT floor FROM tables ORDER BY floor ASC")->fetchAll(PDO::FETCH_COLUMN);
$default_floor = $floors[0] ?? 'Ground Floor';
?>
<div class="container mx-auto px-4" x-data="floorPlan(<?= htmlspecialchars(json_encode($floors)) ?>)">
    <h1 class="text-3xl font-bold mb-6">Live Floor Plan</h1>

    <div class="mb-6 border-b border-gray-200">
        <nav class="flex space-x-4">
            <template x-for="floor in floors" :key="floor">
                <button @click="activeFloor = floor" 
                        :class="{ 'border-orange-500 text-orange-600': activeFloor === floor, 'border-transparent text-gray-500 hover:text-gray-700': activeFloor !== floor }"
                        class="py-3 px-1 border-b-2 font-medium"
                        x-text="floor">
                </button>
            </template>
        </nav>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
        <template x-if="loading">
            <p class="col-span-full text-center text-gray-500">Loading table statuses...</p>
        </template>
        <template x-if="!loading && tables.length === 0">
            <p class="col-span-full text-center text-gray-500">No tables found for this floor.</p>
        </template>
        <template x-for="table in tables" :key="table.id">
            <div :class="statusStyles[table.status] || 'bg-gray-400'" 
                 class="text-white p-6 rounded-lg shadow-lg text-center flex flex-col justify-center aspect-square">
                <p class="text-2xl font-bold" x-text="table.table_number"></p>
                <p class="text-sm opacity-80" x-text="table.status.replace('_', ' ')"></p>
            </div>
        </template>
    </div>
</div>

<script>
function floorPlan(floors) {
    return {
        floors: floors,
        activeFloor: floors[0] || 'Ground Floor',
        tables: [],
        loading: true,
        statusStyles: {
            'free': 'bg-green-500 border-green-700',
            'in_use': 'bg-blue-500 border-blue-700',
            'needs_cleaning': 'bg-yellow-400 border-yellow-600',
            'reserved': 'bg-purple-500 border-purple-700',
            'maintenance': 'bg-red-500 border-red-700'
        },
        init() {
            this.fetchTableStatuses(); // Initial fetch
            setInterval(() => this.fetchTableStatuses(), 5000); // Refresh every 5 seconds

            // Watch for when the activeFloor changes and fetch new data
            this.$watch('activeFloor', () => this.fetchTableStatuses());
        },
        fetchTableStatuses() {
            this.loading = true;
            fetch(`/api/get_table_statuses.php?floor=${this.activeFloor}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        this.tables = data.tables;
                    }
                    this.loading = false;
                });
        }
    }
}
</script>

<?php include 'partials/footer.php'; ?>