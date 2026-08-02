<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';

$thongBao = $_SESSION['success'] ?? '';
$loi = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);

$tuKhoa = trim($_GET['q'] ?? '');
$trangThai = trim($_GET['trang_thai'] ?? '');

$sql = '
    SELECT
        id,
        tieu_de,
        slug,
        tom_tat,
        hinh_anh,
        trang_thai,
        ngay_dang,
        ngay_tao
    FROM bai_viet
    WHERE 1 = 1
';

$params = [];

if ($tuKhoa !== '') {
    $sql .= ' AND tieu_de LIKE :tu_khoa';
    $params['tu_khoa'] = '%' . $tuKhoa . '%';
}

if (in_array($trangThai, ['nhap', 'xuat_ban'], true)) {
    $sql .= ' AND trang_thai = :trang_thai';
    $params['trang_thai'] = $trangThai;
}

$sql .= ' ORDER BY id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$danhSachBaiViet = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Quản lý bài viết</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        .article-image {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
        }

        .no-image {
            width: 100px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background-color: #e9ecef;
            color: #6c757d;
            font-size: 13px;
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-success">
        <div class="container">
            <a
                class="navbar-brand fw-bold"
                href="/dac-san-ca-mau/admin/index.php"
            >
                Quản trị đặc sản Cà Mau
            </a>

            <div>
                <span class="text-white me-3">
                    <?= htmlspecialchars(
                        $_SESSION['admin_name'] ?? 'Admin'
                    ) ?>
                </span>

                <a
                    href="/dac-san-ca-mau/logout.php"
                    class="btn btn-outline-light btn-sm"
                >
                    Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <div
            class="d-flex justify-content-between
                   align-items-center flex-wrap gap-3 mb-4"
        >
            <div>
                <h1 class="h3 mb-1">
                    Quản lý bài viết
                </h1>

                <p class="text-muted mb-0">
                    Quản lý các câu chuyện văn hóa và đặc sản Cà Mau.
                </p>
            </div>

            <a href="create.php" class="btn btn-success">
                + Thêm bài viết
            </a>
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
                            Tìm kiếm bài viết
                        </label>

                        <input
                            type="text"
                            id="q"
                            name="q"
                            class="form-control"
                            placeholder="Nhập tiêu đề bài viết..."
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
                                value="nhap"
                                <?= $trangThai === 'nhap'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Bản nháp
                            </option>

                            <option
                                value="xuat_ban"
                                <?= $trangThai === 'xuat_ban'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Đã xuất bản
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
                                <th style="width: 125px;">Hình ảnh</th>
                                <th>Tiêu đề</th>
                                <th style="width: 130px;">Trạng thái</th>
                                <th style="width: 150px;">Ngày đăng</th>
                                <th style="width: 180px;">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($danhSachBaiViet)): ?>
                                <tr>
                                    <td
                                        colspan="6"
                                        class="text-center text-muted py-4"
                                    >
                                        Chưa có bài viết nào.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (
                                    $danhSachBaiViet as $index => $baiViet
                                ): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>

                                        <td>
                                            <?php if (!empty($baiViet['hinh_anh'])): ?>
                                                <img
                                                    src="/dac-san-ca-mau/assets/uploads/bai-viet/<?= htmlspecialchars(
                                                        $baiViet['hinh_anh']
                                                    ) ?>"
                                                    alt="<?= htmlspecialchars(
                                                        $baiViet['tieu_de']
                                                    ) ?>"
                                                    class="article-image"
                                                >
                                            <?php else: ?>
                                                <div class="no-image">
                                                    Chưa có ảnh
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= htmlspecialchars(
                                                    $baiViet['tieu_de']
                                                ) ?>
                                            </strong>

                                            <?php if (!empty($baiViet['tom_tat'])): ?>
                                                <div class="small text-muted mt-1">
                                                    <?= htmlspecialchars(
                                                        mb_strimwidth(
                                                            $baiViet['tom_tat'],
                                                            0,
                                                            100,
                                                            '...'
                                                        )
                                                    ) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if (
                                                $baiViet['trang_thai']
                                                === 'xuat_ban'
                                            ): ?>
                                                <span class="badge bg-success">
                                                    Đã xuất bản
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    Bản nháp
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if (!empty($baiViet['ngay_dang'])): ?>
                                                <?= date(
                                                    'd/m/Y H:i',
                                                    strtotime(
                                                        $baiViet['ngay_dang']
                                                    )
                                                ) ?>
                                            <?php else: ?>
                                                <span class="text-muted">
                                                    Chưa đăng
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <a
                                                href="edit.php?id=<?= (int) $baiViet['id'] ?>"
                                                class="btn btn-warning btn-sm"
                                            >
                                                Sửa
                                            </a>

                                            <form
                                                action="delete.php"
                                                method="post"
                                                class="d-inline"
                                                onsubmit="return confirm(
                                                    'Bạn có chắc muốn xóa bài viết này?'
                                                );"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $baiViet['id'] ?>"
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
            href="/dac-san-ca-mau/admin/index.php"
            class="btn btn-secondary mt-4"
        >
            Quay lại trang quản trị
        </a>
    </main>
</body>
</html>