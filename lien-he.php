<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

$pageTitle = 'Liên hệ - Đặc sản Cà Mau';
$currentPage = 'lien-he';

$loi = [];
$thongBao = $_SESSION['contact_success'] ?? '';
unset($_SESSION['contact_success']);

$hoTen = $_SESSION['user_name']
    ?? $_SESSION['user_username']
    ?? '';

$email = '';
$soDienThoai = '';
$chuDe = '';
$noiDung = '';

if (empty($_SESSION['csrf_contact'])) {
    $_SESSION['csrf_contact'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrfToken)
        || !hash_equals($_SESSION['csrf_contact'], $csrfToken)
    ) {
        $loi[] = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng thử lại.';
    }

    $hoTen = trim($_POST['ho_ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $soDienThoai = trim($_POST['so_dien_thoai'] ?? '');
    $chuDe = trim($_POST['chu_de'] ?? '');
    $noiDung = trim($_POST['noi_dung'] ?? '');

    if ($hoTen === '') {
        $loi[] = 'Vui lòng nhập họ tên hoặc tên người gửi.';
    }

    if (
        $email !== ''
        && !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $loi[] = 'Địa chỉ email không hợp lệ.';
    }

    if (
        $soDienThoai !== ''
        && !preg_match('/^[0-9+\s().-]{8,20}$/', $soDienThoai)
    ) {
        $loi[] = 'Số điện thoại không hợp lệ.';
    }

    if ($noiDung === '') {
        $loi[] = 'Vui lòng nhập nội dung liên hệ.';
    } elseif (mb_strlen($noiDung) < 10) {
        $loi[] = 'Nội dung liên hệ phải có ít nhất 10 ký tự.';
    }

    if (empty($loi)) {
        $stmt = $pdo->prepare(
            'INSERT INTO lien_he
                (
                    ho_ten,
                    email,
                    so_dien_thoai,
                    chu_de,
                    noi_dung,
                    trang_thai
                )
             VALUES
                (
                    :ho_ten,
                    :email,
                    :so_dien_thoai,
                    :chu_de,
                    :noi_dung,
                    "moi"
                )'
        );

        $stmt->execute([
            'ho_ten' => $hoTen,
            'email' => $email !== '' ? $email : null,
            'so_dien_thoai' => $soDienThoai !== ''
                ? $soDienThoai
                : null,
            'chu_de' => $chuDe !== '' ? $chuDe : null,
            'noi_dung' => $noiDung
        ]);

        $_SESSION['contact_success'] =
            'Cảm ơn bạn đã gửi thông tin. Chúng tôi sẽ xem phản hồi sớm nhất.';

        $_SESSION['csrf_contact'] = bin2hex(random_bytes(32));

        header('Location: /dac-san-ca-mau/lien-he.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
    .contact-banner {
        padding: 65px 0;
        color: #ffffff;
        text-align: center;
        background:
            linear-gradient(
                rgba(20, 77, 54, 0.86),
                rgba(20, 77, 54, 0.86)
            ),
            url("/dac-san-ca-mau/assets/images/banner-ca-mau.jpg")
            center / cover no-repeat;
    }

    .contact-card {
        border: 0;
        border-radius: 14px;
    }

    .contact-information {
        height: 100%;
        padding: 30px;
        color: #ffffff;
        border-radius: 14px;
        background-color: #185a40;
    }
</style>

<section class="contact-banner">
    <div class="container">
        <h1 class="fw-bold">Liên hệ</h1>

        <p class="lead mb-0">
            Gửi câu hỏi, góp ý hoặc thông tin cần tư vấn cho chúng tôi.
        </p>
    </div>
</section>

<main class="container py-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card contact-card shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <h2 class="h3 text-success mb-4">
                        Gửi thông tin liên hệ
                    </h2>

                    <?php if ($thongBao !== ''): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($thongBao) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($loi)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($loi as $noiDungLoi): ?>
                                    <li>
                                        <?= htmlspecialchars($noiDungLoi) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(
                                $_SESSION['csrf_contact']
                            ) ?>"
                        >

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label
                                    for="ho_ten"
                                    class="form-label"
                                >
                                    Tên người gửi
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="ho_ten"
                                    name="ho_ten"
                                    class="form-control"
                                    value="<?= htmlspecialchars($hoTen) ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6 mb-3">
                                <label
                                    for="so_dien_thoai"
                                    class="form-label"
                                >
                                    Số điện thoại
                                </label>

                                <input
                                    type="text"
                                    id="so_dien_thoai"
                                    name="so_dien_thoai"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $soDienThoai
                                    ) ?>"
                                >
                            </div>
                        </div>

                        <div class="mb-3">
                            <label
                                for="email"
                                class="form-label"
                            >
                                Email
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                value="<?= htmlspecialchars($email) ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label
                                for="chu_de"
                                class="form-label"
                            >
                                Chủ đề
                            </label>

                            <input
                                type="text"
                                id="chu_de"
                                name="chu_de"
                                class="form-control"
                                value="<?= htmlspecialchars($chuDe) ?>"
                                placeholder="Ví dụ: Góp ý thông tin đặc sản"
                            >
                        </div>

                        <div class="mb-4">
                            <label
                                for="noi_dung"
                                class="form-label"
                            >
                                Nội dung
                                <span class="text-danger">*</span>
                            </label>

                            <textarea
                                id="noi_dung"
                                name="noi_dung"
                                class="form-control"
                                rows="7"
                                required
                            ><?= htmlspecialchars($noiDung) ?></textarea>
                        </div>

                        <button
                            type="submit"
                            class="btn btn-success px-4"
                        >
                            Gửi liên hệ
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="contact-information shadow-sm">
                <h2 class="h4 mb-4">
                    Website đặc sản Cà Mau
                </h2>

                <p>
                    Website cung cấp thông tin giới thiệu về đặc sản,
                    văn hóa ẩm thực và các cơ sở sản xuất tại Cà Mau.
                </p>

                <hr>

                <p class="mb-2">
                    <strong>Nội dung tiếp nhận:</strong>
                </p>

                <ul>
                    <li>Góp ý thông tin đặc sản.</li>
                    <li>Đề xuất cơ sở sản xuất.</li>
                    <li>Phản hồi nội dung website.</li>
                    <li>Yêu cầu chỉnh sửa thông tin.</li>
                </ul>

                <p class="mb-0">
                    Nhóm thực hiện: <strong>AltF4</strong>
                </p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>