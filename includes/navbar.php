<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$currentPage = $currentPage ?? '';
?>

<?php
$currentPage = $currentPage ?? '';
?>


<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/dac-san-ca-mau/">
            Đặc sản Cà Mau
        </a>

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
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a
                        class="nav-link <?= $currentPage === 'trang-chu' ? 'active' : '' ?>"
                        href="/dac-san-ca-mau/"
                    >
                        Trang chủ
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link <?= $currentPage === 'dac-san' ? 'active' : '' ?>"
                        href="/dac-san-ca-mau/dac-san.php"
                    >
                        Đặc sản
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link <?= $currentPage === 'co-so' ? 'active' : '' ?>"
                        href="/dac-san-ca-mau/co-so-san-xuat.php"
                    >
                        Cơ sở sản xuất
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link <?= $currentPage === 'ban-do' ? 'active' : '' ?>"
                        href="/dac-san-ca-mau/ban-do.php"
                    >
                        Bản đồ đặc sản
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link <?= $currentPage === 'bai-viet' ? 'active' : '' ?>"
                        href="/dac-san-ca-mau/bai-viet.php"
                    >
                        Câu chuyện đặc sản
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link <?= $currentPage === 'lien-he' ? 'active' : '' ?>"
                        href="/dac-san-ca-mau/lien-he.php"
                    >
                        Liên hệ
                    </a>
                </li>
                <?php if (!empty($_SESSION['admin_id'])): ?>
                    <li class="nav-item ms-lg-2">
                        <a
                            class="btn btn-warning"
                            href="/dac-san-ca-mau/admin/index.php"
                        >
                            Trang quản trị
                        </a>
                    </li>

                    <li class="nav-item ms-lg-2">
                        <a
                            class="btn btn-outline-light"
                            href="/dac-san-ca-mau/logout.php"
                        >
                            Đăng xuất
                        </a>
                    </li>

                <?php elseif (!empty($_SESSION['user_id'])): ?>
                    <li class="nav-item ms-lg-2">
                        <a
                            class="btn btn-warning"
                            href="/dac-san-ca-mau/tai-khoan.php"
                        >
                            <?= htmlspecialchars($_SESSION['user_name']) ?>
                        </a>
                    </li>

                    <li class="nav-item ms-lg-2">
                        <a
                            class="btn btn-outline-light"
                            href="/dac-san-ca-mau/logout.php"
                        >
                            Đăng xuất
                        </a>
                    </li>

                <?php else: ?>
                    <li class="nav-item ms-lg-2">
                        <a
                            class="btn btn-warning"
                            href="/dac-san-ca-mau/login.php"
                        >
                            Đăng nhập
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>