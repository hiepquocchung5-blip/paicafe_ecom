<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Ready | Pai Cafe</title>
    <meta name="robots" content="noindex, nofollow, noarchive">
    <link rel="stylesheet" href="/assets/css/tailwind.css?v=<?= filemtime(__DIR__ . '/../assets/css/tailwind.css') ?>">
    <style>
        :root { --cream:#f5efe6; --ink:#241b15; --orange:#c2410c; --green:#1f6b55; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; color:var(--ink); background:radial-gradient(circle at 10% 0%,#fff9ef,transparent 30%),var(--cream); font-family:Poppins,sans-serif; }
        .pickup-header { display:flex; align-items:center; justify-content:space-between; gap:2rem; padding:1.5rem clamp(1rem,4vw,4rem); border-bottom:1px solid #ded3c5; background:rgba(255,252,247,.9); }
        .brand { display:flex; align-items:center; gap:.8rem; }
        .brand-mark { display:grid; place-items:center; width:52px; height:52px; filter:drop-shadow(0 7px 9px rgba(154,52,18,.18)); }
        .brand-mark img { width:100%; height:100%; }
        .brand h1 { margin:0; font-size:clamp(1.2rem,3vw,1.8rem); }
        .brand p,.updated { margin:.15rem 0 0; color:#77695e; font-size:.78rem; }
        main { width:min(1500px,100%); margin:auto; padding:clamp(1.25rem,4vw,4rem); }
        .pickup-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(230px,1fr)); gap:1.25rem; }
        .order-display-card { position:relative; overflow:hidden; min-height:230px; padding:1.5rem; border:1px solid #d9cdbf; border-radius:28px; background:#fffdf9; box-shadow:0 18px 45px rgba(66,46,31,.1); animation:arrive .45s ease both; }
        .order-display-card::before { content:""; position:absolute; inset:0 auto 0 0; width:7px; background:var(--green); }
        .order-label { color:#77695e; font-size:.72rem; font-weight:800; letter-spacing:.14em; text-transform:uppercase; }
        .order-number { margin:.4rem 0 1.5rem; font-size:clamp(3.3rem,8vw,6rem); line-height:1; letter-spacing:-.07em; }
        .order-location { display:inline-flex; padding:.55rem .85rem; border-radius:999px; color:var(--green); background:#e5f1eb; font-size:.85rem; font-weight:700; }
        .empty { display:none; min-height:55vh; place-items:center; text-align:center; }
        .empty.show { display:grid; }
        .empty-icon { display:grid; place-items:center; width:82px; height:82px; margin:auto; border-radius:28px; color:var(--green); background:#e4eee8; font-size:2rem; }
        .empty h2 { margin:1rem 0 .35rem; font-size:2rem; }
        .empty p { margin:0; color:#77695e; }
        @keyframes arrive { from{opacity:0;transform:translateY(18px) scale(.97)} to{opacity:1;transform:none} }
        @media(max-width:600px){.pickup-header{align-items:flex-start;flex-direction:column;gap:.6rem}.pickup-grid{grid-template-columns:1fr 1fr}.order-display-card{min-height:175px;padding:1.1rem}.order-number{font-size:3rem;margin-bottom:1rem}}
        @media(prefers-reduced-motion:reduce){*{animation:none!important}}
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&display=swap">
</head>
<body>
    <header class="pickup-header">
        <div class="brand"><div class="brand-mark"><img src="/assets/svg/pai-mark.svg" alt="Pai Cafe"></div><div><h1>Ready for pickup</h1><p>Pai Cafe &amp; Lounge · Please collect your order at the counter</p></div></div>
        <div class="updated" id="updated">Checking ready orders…</div>
    </header>
    <main>
        <div id="pickup-grid" class="pickup-grid" aria-live="polite"></div>
        <div id="no-orders-message" class="empty"><div><div class="empty-icon">✓</div><h2>Nothing waiting right now</h2><p>Ready orders will appear here automatically.</p></div></div>
    </main>
    <script>
        const grid = document.getElementById('pickup-grid');
        const empty = document.getElementById('no-orders-message');
        const updated = document.getElementById('updated');
        function renderOrders(orders) {
            grid.replaceChildren();
            empty.classList.toggle('show', orders.length === 0);
            orders.forEach((order, index) => {
                const card = document.createElement('article');
                card.className = 'order-display-card';
                card.style.animationDelay = `${Math.min(index * 60, 300)}ms`;
                const label = document.createElement('div'); label.className = 'order-label'; label.textContent = 'Order ready';
                const number = document.createElement('div'); number.className = 'order-number'; number.textContent = `#${order.order_id}`;
                const location = document.createElement('div'); location.className = 'order-location'; location.textContent = order.table_number ? `Table ${order.table_number}` : 'Takeaway';
                card.append(label, number, location); grid.append(card);
            });
            updated.textContent = `Updated ${new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}`;
        }
        async function fetchReadyOrders() {
            try { const response = await fetch('/api/get_ready_orders.php', {cache:'no-store'}); const data = await response.json(); if (data.status === 'success') renderOrders(data.orders || []); }
            catch (error) { updated.textContent = 'Reconnecting…'; console.error('Ready-order sync failed:', error); }
        }
        fetchReadyOrders(); setInterval(fetchReadyOrders, 5000);
    </script>
</body>
</html>
