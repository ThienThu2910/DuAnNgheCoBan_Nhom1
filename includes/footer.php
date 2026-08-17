<footer class="site-footer">
    <div class="container text-center">
        <h5>Website giới thiệu đặc sản Cà Mau</h5>

        <p class="mb-1">
            Giới thiệu văn hóa, ẩm thực và sản vật đặc trưng của vùng đất Cà Mau.
        </p>

        <p class="mb-0">
            &copy; <?= date('Y') ?> Nhóm AltF4
        </p>
    </div>
</footer>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* =====================================================
       SCROLL REVEAL — GIỮ HIỆU ỨNG GIAO DIỆN
    ===================================================== */
    const revealItems = document.querySelectorAll(
        '.home-section, .specialty-card, .facility-card, .article-card, .map-callout, .detail-banner, .article-header, .contact-banner, .facility-banner, .specialty-banner, .map-banner'
    );

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('cm-show');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.10,
            rootMargin: '0px 0px -60px 0px'
        });

        revealItems.forEach(function (item) {
            item.classList.add('cm-reveal');
            observer.observe(item);
        });
    } else {
        revealItems.forEach(function (item) {
            item.classList.add('cm-show');
        });
    }

});
</script>
<!-- BACK TO TOP -->
<button
    type="button"
    id="backToTop"
    class="back-to-top"
    aria-label="Lên đầu trang"
    title="Lên đầu trang"
>
    ↑
</button>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const backToTop = document.getElementById('backToTop');

    if (!backToTop) return;

    function toggleBackToTop() {
        if (window.scrollY > 350) {
            backToTop.classList.add('show');
        } else {
            backToTop.classList.remove('show');
        }
    }

    window.addEventListener('scroll', toggleBackToTop, {
        passive: true
    });

    backToTop.addEventListener('click', function () {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    toggleBackToTop();

});
</script>
</body>
</html>
