<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

$pageTitle = 'Liên hệ & Góp ý - Đặc sản Cà Mau';
$currentPage = 'lien-he';

$loi = [];
$thanhCong = '';

if (empty($_SESSION['csrf_contact'])) {
    $_SESSION['csrf_contact'] = bin2hex(random_bytes(32));
}

$hoTen = '';
$email = '';
$soDienThoai = '';
$chuDe = '';
$noiDung = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_contact'] ?? '', $token)) {
        $loi[] = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
    }

    $hoTen = trim($_POST['ho_ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $soDienThoai = trim($_POST['so_dien_thoai'] ?? '');
    $chuDe = trim($_POST['chu_de'] ?? '');
    $noiDung = trim($_POST['noi_dung'] ?? '');

    if ($hoTen === '') {
        $loi[] = 'Vui lòng nhập họ và tên của bạn.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loi[] = 'Địa chỉ email không đúng định dạng.';
    }

    if ($noiDung === '') {
        $loi[] = 'Vui lòng nhập nội dung liên hệ.';
    }

    if (empty($loi)) {
        $stmt = $pdo->prepare(
            'INSERT INTO lien_he (ho_ten, email, so_dien_thoai, chu_de, noi_dung, trang_thai, ngay_gui)
             VALUES (:ho_ten, :email, :so_dien_thoai, :chu_de, :noi_dung, "moi", NOW())'
        );

        $stmt->execute([
            'ho_ten' => $hoTen,
            'email' => $email !== '' ? $email : null,
            'so_dien_thoai' => $soDienThoai !== '' ? $soDienThoai : null,
            'chu_de' => $chuDe !== '' ? $chuDe : null,
            'noi_dung' => $noiDung,
        ]);

        $_SESSION['csrf_contact'] = bin2hex(random_bytes(32));
        $thanhCong = 'Cảm ơn bạn đã gửi liên hệ! Chúng tôi sẽ phản hồi sớm nhất có thể.';

        $hoTen = '';
        $email = '';
        $soDienThoai = '';
        $chuDe = '';
        $noiDung = '';
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
    .contact-banner {
        padding: 60px 0;
        color: #ffffff;
        text-align: center;
        background: linear-gradient(rgba(20, 77, 54, 0.85), rgba(20, 77, 54, 0.85)),
                    url("<?= htmlspecialchars($baseUrl) ?>/assets/images/banner-ca-mau.jpg") center / cover no-repeat;
    }
    .contact-card {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    }
</style>

<section class="contact-banner">
    <div class="container">
        <h1 class="fw-bold">Liên hệ & Góp ý</h1>
        <p class="lead mb-0">Chúng tôi luôn trân trọng mọi ý kiến đóng góp và thắc mắc của bạn.</p>
    </div>
</section>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card contact-card p-4 p-md-5">
                <?php if (!empty($thanhCong)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($thanhCong) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($loi)): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($loi as $itemLoi): ?>
                                <li><?= htmlspecialchars($itemLoi) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_contact']) ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="ho_ten" class="form-label fw-semibold">Tên người gửi <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                id="ho_ten"
                                name="ho_ten"
                                value="<?= htmlspecialchars($hoTen) ?>"
                                placeholder="Nhập họ và tên..."
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label for="so_dien_thoai" class="form-label fw-semibold">Số điện thoại</label>
                            <input
                                type="tel"
                                class="form-control"
                                id="so_dien_thoai"
                                name="so_dien_thoai"
                                value="<?= htmlspecialchars($soDienThoai) ?>"
                                placeholder="Nhập số điện thoại..."
                            >
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Email liên hệ</label>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars($email) ?>"
                                placeholder="example@domain.com"
                            >
                        </div>

                        <div class="col-md-6">
                            <label for="chu_de" class="form-label fw-semibold">Chủ đề</label>
                            <input
                                type="text"
                                class="form-control"
                                id="chu_de"
                                name="chu_de"
                                value="<?= htmlspecialchars($chuDe) ?>"
                                placeholder="Chủ đề góp ý, hỏi đáp..."
                            >
                        </div>

                        <div class="col-12">
                            <label for="noi_dung" class="form-label fw-semibold">Nội dung liên hệ <span class="text-danger">*</span></label>
                            <textarea
                                class="form-control"
                                id="noi_dung"
                                name="noi_dung"
                                rows="5"
                                placeholder="Nhập nội dung chi tiết..."
                                required
                            ><?= htmlspecialchars($noiDung) ?></textarea>
                        </div>

                        <div class="col-12 text-end mt-4">
                            <button type="submit" class="btn btn-success px-4 py-2 fw-semibold">
                                <i class="bi bi-send-fill me-1"></i> Gửi liên hệ
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>