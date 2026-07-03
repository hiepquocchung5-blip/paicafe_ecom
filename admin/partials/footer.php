            </div>
</main>
        </div>
    </div>
<script>
    window.addEventListener('load', function () {
        const loader = document.getElementById('admin-page-loader');
        if (!loader) return;
        window.setTimeout(function () {
            loader.classList.add('admin-page-loader--hidden');
            window.setTimeout(function () {
                loader.remove();
            }, 300);
        }, 120);
    });
</script>
</body>
</html>
