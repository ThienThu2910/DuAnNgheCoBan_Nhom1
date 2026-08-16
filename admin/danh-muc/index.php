<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';

$thongBao = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

$stmt = $pdo->query(
    'SELECT id, ten_danh_muc, slug, thu_tu, trang_thai, ngay_tao
     FROM danh_muc
     ORDER BY thu_tu ASC, id DESC'
);

$danhMuc = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Quản lý danh mục</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
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
                    <?= htmlspecialchars($_SESSION['admin_name']) ?>
                </span>

                <a
                    href="/DuAnNgheCoBan_Nhom1/admin/logout.php"
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
                   align-items-center flex-wrap gap-2 mb-4"
        >
            <div>
                <h1 class="h3 mb-1">Quản lý danh mục</h1>

                <p class="text-muted mb-0">
                    Quản lý các nhóm đặc sản của website.
                </p>
            </div>

            <a href="create.php" class="btn btn-success">
                + Thêm danh mục
            </a>
        </div>

        <?php if ($thongBao !== ''): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($thongBao) ?>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table
                        class="table table-bordered
                               table-hover align-middle mb-0"
                    >
                        <thead class="table-success">
                            <tr>
                                <th style="width: 70px;">STT</th>
                                <th>Tên danh mục</th>
                                <th>Đường dẫn</th>
                                <th style="width: 100px;">Thứ tự</th>
                                <th style="width: 130px;">Trạng thái</th>
                                <th style="width: 180px;">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($danhMuc)): ?>
                                <tr>
                                    <td
                                        colspan="6"
                                        class="text-center text-muted py-4"
                                    >
                                        Chưa có danh mục nào.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($danhMuc as $index => $item): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $item['ten_danh_muc']
                                            ) ?>
                                        </td>

                                        <td>
                                            <code>
                                                <?= htmlspecialchars(
                                                    $item['slug']
                                                ) ?>
                                            </code>
                                        </td>

                                        <td>
                                            <?= (int) $item['thu_tu'] ?>
                                        </td>

                                        <td>
                                            <?php if ((int) $item['trang_thai'] === 1): ?>
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
                                            <a
                                                href="edit.php?id=<?= (int) $item['id'] ?>"
                                                class="btn btn-warning btn-sm"
                                            >
                                                Sửa
                                            </a>

                                            <form
                                                action="delete.php"
                                                method="post"
                                                class="d-inline"
                                                onsubmit="return confirm(
                                                    'Bạn có chắc muốn xóa danh mục này?'
                                                );"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $item['id'] ?>"
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