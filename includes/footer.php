<footer class="site-footer">
    <div class="container text-center">
        <h5 class="fw-bold mb-2">Website giới thiệu đặc sản Cà Mau</h5>
        <p class="mb-1 text-white-50">
            Giới thiệu văn hóa, ẩm thực và sản vật đặc trưng của vùng đất Cà Mau.
        </p>
        <p class="mb-0 text-white-50 small">
            &copy; <?= date('Y') ?> Nhóm 1 - AltF4
        </p>
    </div>
</footer>

<div id="scrollProgressBar"></div>

<button
    type="button"
    id="backToTop"
    class="back-to-top"
    aria-label="Lên đầu trang"
    title="Lên đầu trang"
>
    &uarr;
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const progressBar = document.getElementById('scrollProgressBar');
    window.addEventListener('scroll', function () {
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        if (scrollHeight > 0 && progressBar) {
            const scrollPercentage = (scrollTop / scrollHeight) * 100;
            progressBar.style.width = scrollPercentage + '%';
        }
    }, { passive: true });

    const targets = document.querySelectorAll(`
        .home-section,
        .specialty-card,
        .facility-card,
        .article-card,
        .story-slider-container,
        .story-box-premium,
        .contact-card
    `);

    targets.forEach(el => {
        el.classList.add('cm-reveal');
    });

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('cm-show');
                } else {
                    entry.target.classList.remove('cm-show');
                }
            });
        }, {
            threshold: 0.15,
            rootMargin: '0px 0px -40px 0px'
        });

        targets.forEach(el => observer.observe(el));
    }

    /* 3. NÚT BACK TO TOP */
    const backToTopBtn = document.getElementById('backToTop');
    if (backToTopBtn) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 280) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        }, { passive: true });

        backToTopBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

});
</script>
</body>
</html>