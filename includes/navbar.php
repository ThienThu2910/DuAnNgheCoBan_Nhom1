<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$currentPage = $currentPage ?? '';

if (!isset($baseUrl) || empty($baseUrl)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $baseUrl = preg_replace('#/(admin|api|includes).*$#', '', $scriptDir);
    $baseUrl = rtrim($baseUrl, '/');
}
?>

<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container navbar-inner">

        <!-- LOGO -->
        <a class="navbar-brand" href="<?= htmlspecialchars($baseUrl) ?>/index.php">
            <span>Đặc sản Cà Mau</span>
        </a>

        <!-- MOBILE MENU -->
        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Mở menu"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">

            <!-- SEARCH -->
            <div class="search-box-container position-relative">
                <div class="search-box">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input
                        type="text"
                        id="live-search-input"
                        placeholder="Tìm đặc sản, cơ sở..."
                        autocomplete="off"
                        aria-label="Tìm kiếm"
                    >
                </div>
                <div
                    id="live-search-results"
                    class="list-group position-absolute w-100 d-none"
                ></div>
            </div>

            <!-- MAIN MENU -->
            <ul class="navbar-nav ms-auto align-items-lg-center main-menu">
                <li class="nav-item">
                    <a
                        class="nav-link <?= $currentPage === 'trang-chu' ? 'active' : '' ?>"
                        href="<?= htmlspecialchars($baseUrl) ?>/index.php"
                    >Trang chủ</a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link <?= $currentPage === 'dac-san' ? 'active' : '' ?>"
                        href="<?= htmlspecialchars($baseUrl) ?>/dac-san.php"
                    >Đặc sản</a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link <?= $currentPage === 'co-so' ? 'active' : '' ?>"
                        href="<?= htmlspecialchars($baseUrl) ?>/co-so-san-xuat.php"
                    >Cơ sở sản xuất</a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link <?= $currentPage === 'ban-do' ? 'active' : '' ?>"
                        href="<?= htmlspecialchars($baseUrl) ?>/ban-do.php"
                    >Bản đồ</a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link <?= $currentPage === 'bai-viet' ? 'active' : '' ?>"
                        href="<?= htmlspecialchars($baseUrl) ?>/bai-viet.php"
                    >Câu chuyện</a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link <?= $currentPage === 'lien-he' ? 'active' : '' ?>"
                        href="<?= htmlspecialchars($baseUrl) ?>/lien-he.php"
                    >Liên hệ</a>
                </li>
            </ul>

            <!-- ACCOUNT -->
            <div class="account-actions">
                <?php if (!empty($_SESSION['admin_id'])): ?>
                    <a
                        class="nav-action nav-action-primary"
                        href="<?= htmlspecialchars($baseUrl) ?>/admin/index.php"
                    >Quản trị</a>

                    <a
                        class="nav-action nav-action-outline"
                        href="<?= htmlspecialchars($baseUrl) ?>/logout.php"
                    >Đăng xuất</a>

                <?php elseif (!empty($_SESSION['user_id'])): ?>
                    <a
                        class="nav-action nav-action-primary"
                        href="<?= htmlspecialchars($baseUrl) ?>/tai-khoan.php"
                    >Tài khoản</a>

                    <a
                        class="nav-action nav-action-outline"
                        href="<?= htmlspecialchars($baseUrl) ?>/logout.php"
                    >Đăng xuất</a>

                <?php else: ?>
                    <a
                        class="nav-action nav-action-primary"
                        href="<?= htmlspecialchars($baseUrl) ?>/login.php"
                    >Đăng nhập</a>
                <?php endif; ?>

                <!-- THEME TOGGLE -->
                <button
                    type="button"
                    class="theme-toggle"
                    id="themeToggle"
                    aria-label="Chuyển chế độ sáng tối"
                    title="Chuyển chế độ sáng tối"
                >
                    <span id="themeIcon">☾</span>
                </button>
            </div>

        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('live-search-input');
    const resultsBox = document.getElementById('live-search-results');
    const baseUrl = <?= json_encode($baseUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    let timeout = null;

    if (input && resultsBox) {
        input.addEventListener('input', function () {
            clearTimeout(timeout);
            const query = this.value.trim();

            if (query.length < 2) {
                resultsBox.classList.add('d-none');
                resultsBox.innerHTML = '';
                return;
            }

            timeout = setTimeout(() => {
                fetch(baseUrl + '/api/live-search.php?q=' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        resultsBox.innerHTML = '';
                        if (!Array.isArray(data) || data.length === 0) {
                            resultsBox.innerHTML = '<div class="list-group-item small text-muted">Không tìm thấy kết quả</div>';
                        } else {
                            data.forEach(item => {
                                const isDacSan = item.loai === 'dac-san';
                                const url = isDacSan
                                    ? baseUrl + '/chi-tiet-dac-san.php?id=' + encodeURIComponent(item.id)
                                    : baseUrl + '/ban-do.php?co_so_id=' + encodeURIComponent(item.id);
                                const folder = isDacSan ? 'dac-san' : 'co-so';
                                const image = item.hinh_anh
                                    ? baseUrl + '/assets/uploads/' + folder + '/' + item.hinh_anh
                                    : baseUrl + '/assets/images/banner-ca-mau.jpg';

                                resultsBox.insertAdjacentHTML(
                                    'beforeend',
                                    `<a href="${url}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 p-2">
                                        <img src="${image}" alt="" class="search-result-image" onerror="this.src='${baseUrl}/assets/images/banner-ca-mau.jpg'">
                                        <div class="small overflow-hidden">
                                            <div class="fw-bold text-truncate">${item.ten ?? ''}</div>
                                            <span class="search-type">${isDacSan ? 'Đặc sản' : 'Cơ sở'}</span>
                                        </div>
                                    </a>`
                                );
                            });
                        }
                        resultsBox.classList.remove('d-none');
                    })
                    .catch(() => {
                        resultsBox.classList.add('d-none');
                    });
            }, 300);
        });

        document.addEventListener('click', function (e) {
            if (!input.contains(e.target) && !resultsBox.contains(e.target)) {
                resultsBox.classList.add('d-none');
            }
        });
    }

    const html = document.documentElement;
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');

    function setTheme(theme) {
        const nextTheme = theme === 'dark' ? 'dark' : 'light';
        html.setAttribute('data-theme', nextTheme);
        try {
            localStorage.setItem('cm-theme', nextTheme);
        } catch (e) {}

        if (themeIcon) {
            themeIcon.textContent = nextTheme === 'dark' ? '☀' : '☾';
        }
    }

    let savedTheme = localStorage.getItem('cm-theme') || 'light';
    setTheme(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const current = html.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
            setTheme(current === 'dark' ? 'light' : 'dark');
        });
    }
});
</script>