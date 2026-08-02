<?php

declare(strict_types=1);

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Tài khoản của tôi';
$currentPage = '';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container py-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h1 class="h3 text-success">
                Xin chào, <?= htmlspecialchars($_SESSION['user_name']) ?>
            </h1>

            <p class="mb-1">
                Tên đăng nhập:
                <strong>
                    <?= htmlspecialchars($_SESSION['user_username']) ?>
                </strong>
            </p>

            <p class="text-muted">
                Bạn có thể tiếp tục khám phá các đặc sản và địa điểm
                nổi bật tại Cà Mau.
            </p>

            <a
                href="/dac-san-ca-mau/dac-san.php"
                class="btn btn-success"
            >
                Xem đặc sản
            </a>

            <a
                href="/dac-san-ca-mau/logout.php"
                class="btn btn-outline-danger"
            >
                Đăng xuất
            </a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>