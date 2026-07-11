<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30"> 
    <title>Orders Ready | Pai Cafe</title>
    <meta name="robots" content="noindex, nofollow, noarchive">
    <link rel="stylesheet" href="/assets/css/tailwind.css?v=<?= filemtime(__DIR__ . '/../assets/css/tailwind.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@700&display=swap');
        .order-display-card {
            font-family: 'Poppins', sans-serif;
            transition: all 0.5s ease-in-out;
            animation: popIn 0.6s ease-out forwards;
        }
        @keyframes popIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body class="bg-gray-100">

    <header class="bg-white shadow-md">
        <div class="container mx-auto p-4 text-center">
            <h1 class="text-4xl font-bold text-orange-600">
                <i class="fas fa-bell"></i> Orders Ready for Pickup
            </h1>
        </div>
    </header>

    <main class="container mx-auto p-4 md:p-8">
        <div id="pickup-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
            </div>
        <div id="no-orders-message" class="hidden text-center py-20">
            <p class="text-2xl text-gray-400">No orders are currently ready for pickup.</p>
        </div>
    </main>

    <script>
        function fetchReadyOrders() {
            fetch('/api/get_ready_orders.php')
                .then(response => response.json())
                .then(data => {
                    const grid = document.getElementById('pickup-grid');
                    const noOrdersMessage = document.getElementById('no-orders-message');

                    if (data.status === 'success') {
                        // Show message if no orders are ready
                        if (data.orders.length === 0) {
                            noOrdersMessage.classList.remove('hidden');
                        } else {
                            noOrdersMessage.classList.add('hidden');
                        }

                        // Clear the grid to redraw
                        grid.innerHTML = ''; 

                        // Add a card for each ready order
                        data.orders.forEach(order => {
                            const cardHtml = `
                                <div class="order-display-card bg-gradient-to-br from-green-400 to-teal-500 text-white p-6 rounded-xl shadow-lg text-center">
                                    <p class="text-lg font-semibold">${order.table_number || 'Takeaway'}</p>
                                    <p class="text-5xl font-bold">#${order.order_id}</p>
                                </div>
                            `;
                            grid.innerHTML += cardHtml;
                        });
                    }
                })
                .catch(error => console.error('Error fetching ready orders:', error));
        }

        // Fetch orders every 5 seconds
        setInterval(fetchReadyOrders, 5000);

        // Initial fetch on page load
        fetchReadyOrders();
    </script>

</body>
</html>
