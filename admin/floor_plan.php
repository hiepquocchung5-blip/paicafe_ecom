<?php
require_once __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/partials/header.php';

// Fetch all unique floor names to create the tabs
$floors = $pdo->query("SELECT DISTINCT floor FROM tables ORDER BY floor ASC")->fetchAll(PDO::FETCH_COLUMN);
$default_floor = $floors[0] ?? 'Ground Floor';
?>
<div class="max-w-7xl mx-auto" x-data="floorPlan(<?= htmlspecialchars(json_encode($floors)) ?>)">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-violet-500 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">Live Dining Room</h2>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-800 dark:text-white tracking-tight leading-none">Floor Plan</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Auto-refreshing table status map for each floor.</p>
        </div>
        <div class="liquid-surface rounded-2xl px-5 py-4 border">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Floors</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white leading-none mt-1"><?= count($floors) ?></p>
        </div>
    </div>

    <div class="liquid-surface rounded-2xl border p-2 mb-8 overflow-x-auto">
        <nav class="flex gap-2 min-w-max">
            <template x-for="floor in floors" :key="floor">
                <button @click="activeFloor = floor" 
                        :class="{ 'bg-violet-600 text-white shadow-lg': activeFloor === floor, 'text-slate-500 hover:text-slate-800 dark:hover:text-white hover:bg-white/40': activeFloor !== floor }"
                        class="px-5 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all"
                        x-text="floor">
                </button>
            </template>
        </nav>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-5">
        <template x-if="loading">
            <div class="col-span-full liquid-surface rounded-2xl border p-10 text-center text-slate-500 font-bold">Loading table statuses...</div>
        </template>
        <template x-if="!loading && tables.length === 0">
            <div class="col-span-full liquid-surface rounded-2xl border p-10 text-center text-slate-500 font-bold">No tables found for this floor.</div>
        </template>
        <template x-for="table in tables" :key="table.id">
            <div :class="statusStyles[table.status] || 'bg-gray-400'" 
                 class="text-white p-5 rounded-[1.5rem] shadow-2xl text-center flex flex-col justify-center aspect-square border border-white/20 transition-transform hover:-translate-y-1">
                <p class="text-2xl font-black" x-text="table.table_number"></p>
                <p class="text-[10px] font-black uppercase tracking-widest opacity-85 mt-2" x-text="table.status.replace('_', ' ')"></p>
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
            fetch(`/api/get_table_statuses.php?floor=${encodeURIComponent(this.activeFloor)}`)
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
