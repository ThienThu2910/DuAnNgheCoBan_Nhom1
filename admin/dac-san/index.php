<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';

$thongBao = $_SESSION['success'] ?? '';
$loi = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);

$tuKhoa = trim($_GET['q'] ?? '');
$danhMucId = filter_input(
    INPUT_GET,
    'danh_muc_id',
    FILTER_VALIDATE_INT
);

$sql = '
    SELECT
        ds.id,
        ds.ten_dac_san,
        ds.slug,
        ds.mo_ta_ngan,
        ds.hinh_anh,
        ds.noi_bat,
        ds.trang_thai,
        ds.ngay_tao,
        dm.ten_danh_muc
    FROM dac_san AS ds
    LEFT JOIN danh_muc AS dm
        ON dm.id = ds.danh_muc_id
    WHERE 1 = 1
';

$params = [];

if ($tuKhoa !== '') {
    $sql .= ' AND ds.ten_dac_san LIKE :tu_khoa';
    $params['tu_khoa'] = '%' . $tuKhoa . '%';
}

if ($danhMucId) {
    $sql .= ' AND ds.danh_muc_id = :danh_muc_id';
    $params['danh_muc_id'] = $danhMucId;
}

$sql .= ' ORDER BY ds.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$danhSachDacSan = $stmt->fetchAll();

$stmtDanhMuc = $pdo->query(
    'SELECT id, ten_danh_muc
     FROM danh_muc
     ORDER BY thu_tu ASC, ten_danh_muc ASC'
);

$danhSachDanhMuc = $stmtDanhMuc->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Quản lý đặc sản</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        .product-image {
            width: 90px;
            height: 65px;
            object-fit: cover;
            border-radius: 6px;
        }

        .no-image {
            width: 90px;
            height: 65px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
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
                href="/DuAnNgheCoBan_Nhom1/admin/index.php"
            >
                Quản trị đặc sản Cà Mau
            </a>

            <div>
                <span class="text-white me-3">
                    <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
                </span>

                <a
                    href="/DuAnNgheCoBan_Nhom1/logout.php"
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
                <h1 class="h3 mb-1">Quản lý đặc sản</h1>

                <p class="text-muted mb-0">
                    Quản lý thông tin các đặc sản được giới thiệu trên website.
                </p>
            </div>

            <a href="create.php" class="btn btn-success">
                + Thêm đặc sản
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
                            Tìm kiếm đặc sản
                        </label>

                        <input
                            type="text"
                            id="q"
                            name="q"
                            class="form-control"
                            placeholder="Nhập tên đặc sản..."
                            value="<?= htmlspecialchars($tuKhoa) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label for="danh_muc_id" class="form-label">
                            Danh mục
                        </label>

                        <select
                            id="danh_muc_id"
                            name="danh_muc_id"
                            class="form-select"
                        >
                            <option value="">Tất cả danh mục</option>

                            <?php foreach ($danhSachDanhMuc as $danhMuc): ?>
                                <option
                                    value="<?= (int) $danhMuc['id'] ?>"
                                    <?= $danhMucId === (int) $danhMuc['id']
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= htmlspecialchars(
                                        $danhMuc['ten_danh_muc']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button
                            type="submit"
                            class="btn btn-success w-100"
                        >
                            Lọc
                        </button>

                        <a
                            href="index.php"
                            class="btn btn-secondary"
                        >
                            Xóa lọc
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
                               table-hover align-middle mb-0">
                        <thead class="table-success">
                            <tr>
                                <th style="width: 65px;">STT</th>
                                <th style="width: 120px;">Hình ảnh</th>
                                <th>Tên đặc sản</th>
                                <th>Danh mục</th>
                                <th style="width: 100px;">Nổi bật</th>
                                <th style="width: 120px;">Trạng thái</th>
                                <th style="width: 170px;">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($danhSachDacSan)): ?>
                                <tr>
                                    <td
                                        colspan="7"
                                        class="text-center text-muted py-4">
                                        Chưa có đặc sản nào.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($danhSachDacSan as $index => $dacSan): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>

                                        <td>
                                            <?php if (!empty($dacSan['hinh_anh'])): ?>
                                                <img
                                                    src="/DuAnNgheCoBan_Nhom1/assets/uploads/dac-san/<?= htmlspecialchars(
                                                        $dacSan['hinh_anh']
                                                    ) ?>"
                                                    alt="<?= htmlspecialchars(
                                                        $dacSan['ten_dac_san']
                                                    ) ?>"
                                                    class="product-image"
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
                                                    $dacSan['ten_dac_san']
                                                ) ?>
                                            </strong>

                                            <?php if (!empty($dacSan['mo_ta_ngan'])): ?>
                                                <div class="text-muted small mt-1">
                                                    <?= htmlspecialchars(
                                                        mb_strimwidth(
                                                            $dacSan['mo_ta_ngan'],
                                                            0,
                                                            100,
                                                            '...'
                                                        )
                                                    ) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $dacSan['ten_danh_muc']
                                                    ?? 'Chưa phân loại'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?php if ((int) $dacSan['noi_bat'] === 1): ?>
                                                <span class="badge bg-warning text-dark">
                                                    Nổi bật
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">
                                                    Không
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if ((int) $dacSan['trang_thai'] === 1): ?>
                                                <span class="badge bg-success">
                                                    Hiển thị
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    Đang ẩn
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <a href="edit.php?id=<?= (int) $dacSan['id'] ?>"
                                                class="btn btn-warning btn-sm">
                                                Sửa
                                            </a>

                                            <form
                                                method="post"
                                                action="delete.php"
                                                class="d-inline"
                                                onsubmit="return confirm(
                                                    'Bạn có chắc muốn xóa đặc sản này?'
                                                );"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $dacSan['id'] ?>"
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