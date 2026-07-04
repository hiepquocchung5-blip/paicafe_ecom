            </div>
</main>
        </div>
    </div>
<script>
    (function () {
        const loader = document.getElementById('admin-page-loader');
        if (!loader) return;

        let hidden = false;
        const hideLoader = function () {
            if (hidden) return;
            hidden = true;
            loader.classList.add('admin-page-loader--hidden');
            window.setTimeout(function () {
                if (loader && loader.parentNode) loader.remove();
            }, 360);
        };

        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            window.setTimeout(hideLoader, 180);
        } else {
            document.addEventListener('DOMContentLoaded', function () {
                window.setTimeout(hideLoader, 180);
            }, { once: true });
        }

        window.addEventListener('load', function () {
            window.setTimeout(hideLoader, 80);
        }, { once: true });

        window.setTimeout(hideLoader, 1800);
    })();
</script>
</body>
</html>
