<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

/*
 * Lấy các số liệu tổng quan.
 */
$tongDacSan = (int) $pdo
    ->query('SELECT COUNT(*) FROM dac_san')
    ->fetchColumn();

$tongDanhMuc = (int) $pdo
    ->query('SELECT COUNT(*) FROM danh_muc')
    ->fetchColumn();

$tongCoSo = (int) $pdo
    ->query('SELECT COUNT(*) FROM co_so_san_xuat')
    ->fetchColumn();

$tongBaiViet = (int) $pdo
    ->query(
        "SELECT COUNT(*)
         FROM bai_viet
         WHERE trang_thai = 'xuat_ban'"
    )
    ->fetchColumn();

$tongNguoiDung = (int) $pdo
    ->query('SELECT COUNT(*) FROM nguoi_dung')
    ->fetchColumn();

$lienHeMoi = (int) $pdo
    ->query(
        "SELECT COUNT(*)
         FROM lien_he
         WHERE trang_thai = 'moi'"
    )
    ->fetchColumn();

/*
 * Lấy 5 liên hệ mới nhất.
 */
$stmtLienHe = $pdo->query(
    'SELECT
        id,
        ho_ten,
        chu_de,
        trang_thai,
        ngay_gui
     FROM lien_he
     ORDER BY ngay_gui DESC
     LIMIT 5'
);

$danhSachLienHeMoi = $stmtLienHe->fetchAll();

function hienThiTrangThaiLienHe(string $trangThai): string
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

    <title>Trang quản trị</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background-color: #f5f7f6;
        }

        .stat-card {
            height: 100%;
            border: 0;
            border-radius: 14px;
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.10);
        }

        .stat-number {
            color: #198754;
            font-size: 36px;
            font-weight: 700;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 18px;
        }

        .admin-function {
            min-height: 105px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
            border-radius: 12px;
            color: #ffffff;
            background-color: #198754;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .admin-function:hover {
            color: #ffffff;
            background-color: #157347;
            transform: translateY(-4px);
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.12);
        }

        @media (max-width: 1199px) {
            .admin-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 767px) {
            .admin-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .admin-header {
                align-items: flex-start !important;
                flex-direction: column;
            }
        }

        @media (max-width: 575px) {
            .admin-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-success">
        <div class="container">
            <a
                class="navbar-brand fw-bold"
                href="/dac-san-ca-mau/admin/index.php"
            >
                Quản trị đặc sản Cà Mau
            </a>

            <div class="d-flex align-items-center gap-3">
                <span class="text-white d-none d-md-inline">
                    Xin chào,
                    <?= htmlspecialchars(
                        $_SESSION['admin_name'] ?? 'Quản trị viên'
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

    <main class="container py-5">
        <div
            class="admin-header d-flex justify-content-between
                   align-items-end gap-3 mb-4"
        >
            <div>
                <h1 class="display-6 fw-bold mb-1">
                    Tổng quan hệ thống
                </h1>

                <p class="text-muted mb-0">
                    Theo dõi và quản lý nội dung website đặc sản Cà Mau.
                </p>
            </div>

            <a
                href="/dac-san-ca-mau/"
                class="btn btn-secondary"
            >
                Về trang chủ
            </a>
        </div>

        <!-- Thống kê -->
        <section class="mb-5">
            <div class="row g-4">
                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body">
                            <div class="stat-number">
                                <?= $tongDacSan ?>
                            </div>

                            <div class="text-muted">
                                Đặc sản
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body">
                            <div class="stat-number">
                                <?= $tongDanhMuc ?>
                            </div>

                            <div class="text-muted">
                                Danh mục
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body">
                            <div class="stat-number">
                                <?= $tongCoSo ?>
                            </div>

                            <div class="text-muted">
                                Cơ sở
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body">
                            <div class="stat-number">
                                <?= $tongBaiViet ?>
                            </div>

                            <div class="text-muted">
                                Bài viết
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body">
                            <div class="stat-number">
                                <?= $tongNguoiDung ?>
                            </div>

                            <div class="text-muted">
                                Người dùng
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body">
                            <div class="stat-number text-danger">
                                <?= $lienHeMoi ?>
                            </div>

                            <div class="text-muted">
                                Liên hệ mới
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Chức năng quản trị -->
        <section class="mb-5">
            <h2 class="h4 mb-3">
                Chức năng quản trị
            </h2>

            <div class="admin-grid">
                <a
                    href="/dac-san-ca-mau/admin/dac-san/"
                    class="admin-function"
                >
                    Quản lý đặc sản
                </a>

                <a
                    href="/dac-san-ca-mau/admin/danh-muc/"
                    class="admin-function"
                >
                    Quản lý danh mục
                </a>

                <a
                    href="/dac-san-ca-mau/admin/co-so/"
                    class="admin-function"
                >
                    Quản lý cơ sở sản xuất
                </a>

                <a
                    href="/dac-san-ca-mau/admin/bai-viet/"
                    class="admin-function"
                >
                    Quản lý bài viết
                </a>

                <a
                    href="/dac-san-ca-mau/admin/lien-he/"
                    class="admin-function"
                >
                    Quản lý liên hệ

                    <?php if ($lienHeMoi > 0): ?>
                        <span class="badge bg-danger ms-2">
                            <?= $lienHeMoi ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
        </section>

        <!-- Liên hệ gần đây -->
        <section>
            <div
                class="d-flex justify-content-between
                       align-items-center mb-3"
            >
                <h2 class="h4 mb-0">
                    Liên hệ gần đây
                </h2>

                <a
                    href="/dac-san-ca-mau/admin/lien-he/"
                    class="btn btn-outline-success btn-sm"
                >
                    Xem tất cả
                </a>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table
                            class="table table-hover align-middle mb-0"
                        >
                            <thead class="table-success">
                                <tr>
                                    <th>Người gửi</th>
                                    <th>Chủ đề</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày gửi</th>
                                    <th style="width: 90px;">
                                        Thao tác
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($danhSachLienHeMoi)): ?>
                                    <tr>
                                        <td
                                            colspan="5"
                                            class="text-center
                                                   text-muted py-4"
                                        >
                                            Chưa có thông tin liên hệ.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach (
                                        $danhSachLienHeMoi as $lienHe
                                    ): ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars(
                                                    $lienHe['ho_ten']
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $lienHe['chu_de']
                                                        ?: 'Không có chủ đề'
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= hienThiTrangThaiLienHe(
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
                                                    href="/dac-san-ca-mau/admin/lien-he/detail.php?id=<?= (int) $lienHe['id'] ?>"
                                                    class="btn btn-primary btn-sm"
                                                >
                                                    Xem
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    ></script>
</body>
</html>