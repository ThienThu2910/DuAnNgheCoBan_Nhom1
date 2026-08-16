<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';

$thongBao = $_SESSION['success'] ?? '';
$loi = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);

if (empty($_SESSION['csrf_admin_contact'])) {
    $_SESSION['csrf_admin_contact'] = bin2hex(random_bytes(32));
}

$tuKhoa = trim($_GET['q'] ?? '');
$trangThai = trim($_GET['trang_thai'] ?? '');

$sql = '
    SELECT
        id,
        ho_ten,
        email,
        so_dien_thoai,
        chu_de,
        noi_dung,
        trang_thai,
        ngay_gui
    FROM lien_he
    WHERE 1 = 1
';

$params = [];

if ($tuKhoa !== '') {
    $sql .= '
        AND (
            ho_ten LIKE :q1
            OR email LIKE :q2
            OR chu_de LIKE :q3
            OR noi_dung LIKE :q4
        )
    ';

    $giaTriTimKiem = '%' . $tuKhoa . '%';

    $params['q1'] = $giaTriTimKiem;
    $params['q2'] = $giaTriTimKiem;
    $params['q3'] = $giaTriTimKiem;
    $params['q4'] = $giaTriTimKiem;
}

if (
    in_array(
        $trangThai,
        ['moi', 'da_xem', 'da_phan_hoi'],
        true
    )
) {
    $sql .= ' AND trang_thai = :trang_thai';
    $params['trang_thai'] = $trangThai;
}

$sql .= '
    ORDER BY
        CASE trang_thai
            WHEN "moi" THEN 1
            WHEN "da_xem" THEN 2
            ELSE 3
        END,
        ngay_gui DESC
';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$danhSachLienHe = $stmt->fetchAll();

function hienThiTrangThai(string $trangThai): string
{
    return match ($trangThai) {
        'moi' => '<span class="badge bg-danger">Mới</span>',
        'da_xem' => '<span class="badge bg-warning text-dark">Đã xem</span>',
        'da_phan_hoi' => '<span class="badge bg-success">Đã phản hồi</span>',
        default => '<span class="badge bg-secondary">Không xác định</span>'
    };
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

    <title>Quản lý liên hệ</title>

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

    <main class="container py-4">
        <div class="mb-4">
            <h1 class="h3 mb-1">
                Quản lý liên hệ
            </h1>

            <p class="text-muted mb-0">
                Xem và xử lý các phản hồi được gửi từ website.
            </p>
        </div>

        <?php if ($thongBao !== ''): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($thongBao) ?>
            </div>
        <?php endif; ?>

        <?php if ($loi !== ''): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($loi) ?>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-6">
                        <label for="q" class="form-label">
                            Tìm kiếm
                        </label>

                        <input
                            type="text"
                            id="q"
                            name="q"
                            class="form-control"
                            placeholder="Tên, email, chủ đề hoặc nội dung..."
                            value="<?= htmlspecialchars($tuKhoa) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label
                            for="trang_thai"
                            class="form-label"
                        >
                            Trạng thái
                        </label>

                        <select
                            id="trang_thai"
                            name="trang_thai"
                            class="form-select"
                        >
                            <option value="">
                                Tất cả trạng thái
                            </option>

                            <option
                                value="moi"
                                <?= $trangThai === 'moi'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Mới
                            </option>

                            <option
                                value="da_xem"
                                <?= $trangThai === 'da_xem'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Đã xem
                            </option>

                            <option
                                value="da_phan_hoi"
                                <?= $trangThai === 'da_phan_hoi'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Đã phản hồi
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button
                            type="submit"
                            class="btn btn-success flex-grow-1"
                        >
                            Lọc
                        </button>

                        <a
                            href="index.php"
                            class="btn btn-secondary"
                        >
                            Xóa
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table
                        class="table table-bordered
                               table-hover align-middle mb-0"
                    >
                        <thead class="table-success">
                            <tr>
                                <th style="width: 65px;">STT</th>
                                <th>Người gửi</th>
                                <th>Chủ đề</th>
                                <th style="width: 140px;">Trạng thái</th>
                                <th style="width: 160px;">Ngày gửi</th>
                                <th style="width: 175px;">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($danhSachLienHe)): ?>
                                <tr>
                                    <td
                                        colspan="6"
                                        class="text-center text-muted py-4"
                                    >
                                        Chưa có thông tin liên hệ.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (
                                    $danhSachLienHe as $index => $lienHe
                                ): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>

                                        <td>
                                            <strong>
                                                <?= htmlspecialchars(
                                                    $lienHe['ho_ten']
                                                ) ?>
                                            </strong>

                                            <?php if (!empty($lienHe['email'])): ?>
                                                <div class="small text-muted">
                                                    <?= htmlspecialchars(
                                                        $lienHe['email']
                                                    ) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $lienHe['chu_de']
                                                    ?: 'Không có chủ đề'
                                            ) ?>

                                            <div class="small text-muted mt-1">
                                                <?= htmlspecialchars(
                                                    mb_strimwidth(
                                                        $lienHe['noi_dung'],
                                                        0,
                                                        90,
                                                        '...'
                                                    )
                                                ) ?>
                                            </div>
                                        </td>

                                        <td>
                                            <?= hienThiTrangThai(
                                                $lienHe['trang_thai']
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= date(
                                                'd/m/Y H:i',
                                                strtotime(
                                                    $lienHe['ngay_gui']
                                                )
                                            ) ?>
                                        </td>

                                        <td>
                                            <a
                                                href="detail.php?id=<?= (int) $lienHe['id'] ?>"
                                                class="btn btn-primary btn-sm"
                                            >
                                                Xem
                                            </a>

                                            <form
                                                method="post"
                                                action="delete.php"
                                                class="d-inline"
                                                onsubmit="return confirm(
                                                    'Bạn có chắc muốn xóa liên hệ này?'
                                                );"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $lienHe['id'] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= htmlspecialchars(
                                                        $_SESSION[
                                                            'csrf_admin_contact'
                                                        ]
                                                    ) ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-danger btn-sm"
                                                >
                                                    Xóa
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <a
            href="/DuAnNgheCoBan_Nhom1/admin/index.php"
            class="btn btn-secondary mt-4"
        >
            Quay lại trang quản trị
        </a>
    </main>
</body>
</html>