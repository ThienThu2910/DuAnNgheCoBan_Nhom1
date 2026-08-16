<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['error'] = 'Mã liên hệ không hợp lệ.';
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_admin_contact'])) {
    $_SESSION['csrf_admin_contact'] = bin2hex(random_bytes(32));
}

$stmt = $pdo->prepare(
    'SELECT *
     FROM lien_he
     WHERE id = :id
     LIMIT 1'
);

$stmt->execute(['id' => $id]);

$lienHe = $stmt->fetch();

if (!$lienHe) {
    $_SESSION['error'] = 'Không tìm thấy nội dung liên hệ.';
    header('Location: index.php');
    exit;
}

/*
 * Tự chuyển trạng thái từ mới sang đã xem.
 */
if ($lienHe['trang_thai'] === 'moi') {
    $capNhatDaXem = $pdo->prepare(
        'UPDATE lien_he
         SET trang_thai = "da_xem"
         WHERE id = :id'
    );

    $capNhatDaXem->execute(['id' => $id]);
    $lienHe['trang_thai'] = 'da_xem';
}

$loi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $trangThai = $_POST['trang_thai'] ?? '';

    if (
        !is_string($csrfToken)
        || !hash_equals(
            $_SESSION['csrf_admin_contact'],
            $csrfToken
        )
    ) {
        $loi = 'Phiên cập nhật không hợp lệ.';
    } elseif (
        !in_array(
            $trangThai,
            ['moi', 'da_xem', 'da_phan_hoi'],
            true
        )
    ) {
        $loi = 'Trạng thái không hợp lệ.';
    } else {
        $capNhat = $pdo->prepare(
            'UPDATE lien_he
             SET trang_thai = :trang_thai
             WHERE id = :id'
        );

        $capNhat->execute([
            'trang_thai' => $trangThai,
            'id' => $id
        ]);

        $_SESSION['success'] =
            'Cập nhật trạng thái liên hệ thành công.';

        header('Location: index.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Chi tiết liên hệ</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-success">
        <div class="container">
            <a
                href="/DuAnNgheCoBan_Nhom1/admin/index.php"
                class="navbar-brand fw-bold"
            >
                Quản trị đặc sản Cà Mau
            </a>

            <a
                href="/DuAnNgheCoBan_Nhom1/logout.php"
                class="btn btn-outline-light btn-sm"
            >
                Đăng xuất
            </a>
        </div>
    </nav>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <div
                            class="d-flex justify-content-between
                                   align-items-center flex-wrap gap-2 mb-4"
                        >
                            <h1 class="h3 mb-0">
                                Chi tiết liên hệ
                            </h1>

                            <a
                                href="index.php"
                                class="btn btn-secondary"
                            >
                                Quay lại
                            </a>
                        </div>

                        <?php if ($loi !== ''): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($loi) ?>
                            </div>
                        <?php endif; ?>

                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 180px;">
                                    Người gửi
                                </th>

                                <td>
                                    <?= htmlspecialchars(
                                        $lienHe['ho_ten']
                                    ) ?>
                                </td>
                            </tr>

                            <tr>
                                <th>Email</th>

                                <td>
                                    <?php if (!empty($lienHe['email'])): ?>
                                        <a
                                            href="mailto:<?= htmlspecialchars(
                                                $lienHe['email']
                                            ) ?>"
                                        >
                                            <?= htmlspecialchars(
                                                $lienHe['email']
                                            ) ?>
                                        </a>
                                    <?php else: ?>
                                        Không cung cấp
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <tr>
                                <th>Số điện thoại</th>

                                <td>
                                    <?php if (
                                        !empty($lienHe['so_dien_thoai'])
                                    ): ?>
                                        <a
                                            href="tel:<?= htmlspecialchars(
                                                $lienHe['so_dien_thoai']
                                            ) ?>"
                                        >
                                            <?= htmlspecialchars(
                                                $lienHe['so_dien_thoai']
                                            ) ?>
                                        </a>
                                    <?php else: ?>
                                        Không cung cấp
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <tr>
                                <th>Chủ đề</th>

                                <td>
                                    <?= htmlspecialchars(
                                        $lienHe['chu_de']
                                            ?: 'Không có chủ đề'
                                    ) ?>
                                </td>
                            </tr>

                            <tr>
                                <th>Ngày gửi</th>

                                <td>
                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime($lienHe['ngay_gui'])
                                    ) ?>
                                </td>
                            </tr>
                        </table>

                        <div class="border rounded p-4 bg-light mb-4">
                            <h2 class="h5 text-success">
                                Nội dung
                            </h2>

                            <div style="white-space: pre-line;">
                                <?= htmlspecialchars(
                                    $lienHe['noi_dung']
                                ) ?>
                            </div>
                        </div>

                        <form method="post">
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars(
                                    $_SESSION['csrf_admin_contact']
                                ) ?>"
                            >

                            <div class="row align-items-end">
                                <div class="col-md-8">
                                    <label
                                        for="trang_thai"
                                        class="form-label"
                                    >
                                        Trạng thái xử lý
                                    </label>

                                    <select
                                        id="trang_thai"
                                        name="trang_thai"
                                        class="form-select"
                                    >
                                        <option
                                            value="moi"
                                            <?= $lienHe['trang_thai'] === 'moi'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Mới
                                        </option>

                                        <option
                                            value="da_xem"
                                            <?= $lienHe['trang_thai']
                                                === 'da_xem'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Đã xem
                                        </option>

                                        <option
                                            value="da_phan_hoi"
                                            <?= $lienHe['trang_thai']
                                                === 'da_phan_hoi'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Đã phản hồi
                                        </option>
                                    </select>
                                </div>

                                <div class="col-md-4 mt-3 mt-md-0">
                                    <button
                                        type="submit"
                                        class="btn btn-success w-100"
                                    >
                                        Cập nhật trạng thái
                                    </button>
                                </div>
                            </div>
                        </form>

                        <?php if (!empty($lienHe['email'])): ?>
                            <a
                                href="mailto:<?= htmlspecialchars(
                                    $lienHe['email']
                                ) ?>?subject=<?= rawurlencode(
                                    'Phản hồi: '
                                    . ($lienHe['chu_de']
                                        ?: 'Liên hệ website đặc sản Cà Mau')
                                ) ?>"
                                class="btn btn-outline-primary mt-3"
                            >
                                Gửi email phản hồi
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>