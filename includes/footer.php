<footer class="mt-5 py-4">
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
document.addEventListener("DOMContentLoaded", function () {

    const items = document.querySelectorAll(
        ".home-section, .specialty-card, .facility-card, .article-card, .map-callout"
    );

    items.forEach(function (item) {
        item.classList.add("cm-reveal");
    });


    const observer = new IntersectionObserver(function (entries) {

        entries.forEach(function (entry) {

            if (entry.isIntersecting) {

                entry.target.classList.add("cm-show");

                observer.unobserve(entry.target);
            }

        });

    }, {
        threshold: 0.12,
        rootMargin: "0px 0px -70px 0px"
    });


    items.forEach(function (item) {
        observer.observe(item);
    });

});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {

    const html = document.documentElement;
    const toggle = document.getElementById("themeToggle");

    if (!toggle) {
        return;
    }


    /* Lấy theme đã lưu */

    const savedTheme = localStorage.getItem("cm-theme");


    if (savedTheme === "dark") {
        html.setAttribute("data-theme", "dark");
        toggle.textContent = "☀";
    } else {
        html.setAttribute("data-theme", "light");
        toggle.textContent = "☾";
    }


    /* Click đổi theme */

    toggle.addEventListener("click", function () {

        const isDark =
            html.getAttribute("data-theme") === "dark";


        if (isDark) {

            html.setAttribute("data-theme", "light");

            localStorage.setItem("cm-theme", "light");

            toggle.textContent = "☾";

        } else {

            html.setAttribute("data-theme", "dark");

            localStorage.setItem("cm-theme", "dark");

            toggle.textContent = "☀";

        }

    });

});
</script>
</body>
</html>