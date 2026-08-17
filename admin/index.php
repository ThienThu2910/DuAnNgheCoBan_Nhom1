<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

/*
 * Lấy số liệu thống kê
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
    ->query("SELECT COUNT(*) FROM bai_viet WHERE trang_thai = 'xuat_ban'")
    ->fetchColumn();

$tongNguoiDung = (int) $pdo
    ->query('SELECT COUNT(*) FROM nguoi_dung')
    ->fetchColumn();

$lienHeMoi = (int) $pdo
    ->query("SELECT COUNT(*) FROM lien_he WHERE trang_thai = 'moi'")
    ->fetchColumn();

/*
 * Lấy 5 liên hệ mới nhất
 */
$stmtLienHe = $pdo->query(
    'SELECT id, ho_ten, chu_de, trang_thai, ngay_gui
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển quản trị - Đặc sản Cà Mau</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 260px;
            --topbar-height: 64px;
            --admin-red: #641f25;
            --admin-red-dark: #3d171a;
            --admin-red-hover: #8a3037;
            --admin-bg: #f4f6f9;
            --admin-card-bg: #ffffff;
            --admin-border: rgba(0, 0, 0, 0.08);
        }

        body {
            margin: 0;
            background-color: var(--admin-bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #2b2b2b;
        }

        /* SIDEBAR */
        .admin-sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1040;
            background: linear-gradient(180deg, var(--admin-red-dark) 0%, var(--admin-red) 100%);
            color: #ffffff;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.12);
            transition: transform 0.3s ease;
        }

        .sidebar-brand {
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            padding: 0 22px;
            font-size: 17px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #ffffff;
            text-decoration: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .sidebar-menu {
            padding: 16px 12px;
            list-style: none;
            margin: 0;
        }

        .sidebar-menu .menu-header {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.45);
            padding: 14px 12px 6px;
        }

        .sidebar-menu .nav-item {
            margin-bottom: 4px;
        }

        .sidebar-menu .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: rgba(255, 255, 255, 0.82);
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .sidebar-menu .nav-link i {
            font-size: 17px;
            width: 22px;
        }

        .sidebar-menu .nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
            transform: translateX(4px);
        }

        .sidebar-menu .nav-link.active {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.22);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* MAIN CONTENT */
        .admin-main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .admin-topbar {
            height: var(--topbar-height);
            background: #ffffff;
            border-bottom: 1px solid var(--admin-border);
            position: sticky;
            top: 0;
            z-index: 1030;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }

        .admin-content {
            padding: 28px;
            flex-grow: 1;
        }

        /* CARDS */
        .admin-card {
            border: 0;
            border-radius: 12px;
            background: var(--admin-card-bg);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        .btn-outline-burgundy {
            color: var(--admin-red) !important;
            border-color: var(--admin-red) !important;
            background: transparent !important;
        }
        .btn-outline-burgundy:hover {
            background-color: var(--admin-red) !important;
            color: #ffffff !important;
        }

        .table thead th {
            background-color: #faf6f6;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555555;
            border-bottom: 2px solid var(--admin-border);
        }

        @media (max-width: 991.98px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.show-sidebar {
                transform: translateX(0);
            }
            .admin-main-wrapper {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="admin-sidebar" id="adminSidebar">
    <a href="/DuAnNgheCoBan_Nhom1/admin/index.php" class="sidebar-brand">
        <i class="bi bi-shield-lock-fill me-2 fs-5"></i> QUẢN TRỊ CÀ MAU
    </a>

    <ul class="sidebar-menu">
        <li class="menu-header">Bảng điều khiển</li>
        <li class="nav-item">
            <a href="/DuAnNgheCoBan_Nhom1/admin/index.php" class="nav-link active">
                <i class="bi bi-grid-1x2-fill"></i> Tổng quan
            </a>
        </li>

        <li class="menu-header">Quản lý nội dung</li>
        <li class="nav-item">
            <a href="/DuAnNgheCoBan_Nhom1/admin/dac-san/" class="nav-link">
                <i class="bi bi-basket2-fill"></i> Quản lý đặc sản
            </a>
        </li>
        <li class="nav-item">
            <a href="/DuAnNgheCoBan_Nhom1/admin/danh-muc/" class="nav-link">
                <i class="bi bi-tags-fill"></i> Quản lý danh mục
            </a>
        </li>
        <li class="nav-item">
            <a href="/DuAnNgheCoBan_Nhom1/admin/co-so/" class="nav-link">
                <i class="bi bi-geo-alt-fill"></i> Quản lý cơ sở
            </a>
        </li>
        <li class="nav-item">
            <a href="/DuAnNgheCoBan_Nhom1/admin/bai-viet/" class="nav-link">
                <i class="bi bi-journal-text"></i> Quản lý bài viết
            </a>
        </li>
        <li class="nav-item">
            <a href="/DuAnNgheCoBan_Nhom1/admin/lien-he/" class="nav-link">
                <i class="bi bi-envelope-fill"></i> Quản lý liên hệ
                <?php if ($lienHeMoi > 0): ?>
                    <span class="badge bg-danger ms-auto"><?= $lienHeMoi ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="menu-header">Hệ thống</li>
        <li class="nav-item">
            <!-- Đã bỏ target="_blank" -->
            <a href="/DuAnNgheCoBan_Nhom1/" class="nav-link">
                <i class="bi bi-box-arrow-up-right"></i> Xem trang chủ
            </a>
        </li>
        <li class="nav-item">
            <a href="/DuAnNgheCoBan_Nhom1/logout.php" class="nav-link text-danger-emphasis">
                <i class="bi bi-box-arrow-left"></i> Đăng xuất
            </a>
        </li>
    </ul>
</aside>

<!-- MAIN WRAPPER -->
<div class="admin-main-wrapper">
    <!-- TOPBAR -->
    <header class="admin-topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-secondary d-lg-none" id="toggleSidebarBtn" type="button">
                <i class="bi bi-list fs-5"></i>
            </button>
            <span class="fw-semibold text-secondary d-none d-md-inline">
                Hệ thống quản trị website đặc sản Cà Mau
            </span>
        </div>

        <div class="d-flex align-items-center gap-3">
            <span class="small fw-semibold text-dark">
                <i class="bi bi-person-circle fs-5 me-1 text-secondary align-middle"></i>
                Xin chào, <?= htmlspecialchars((string)($_SESSION['admin_name'] ?? 'Quản trị viên')) ?>
            </span>
            <a href="/DuAnNgheCoBan_Nhom1/logout.php" class="btn btn-sm btn-outline-danger px-3">
                Đăng xuất
            </a>
        </div>
    </header>

    <!-- CONTENT -->
    <main class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Tổng quan hệ thống</h1>
                <p class="text-muted mb-0">Theo dõi số liệu và hoạt động dữ liệu đặc sản Cà Mau.</p>
            </div>
            <!-- Đã bỏ target="_blank" -->
            <a href="/DuAnNgheCoBan_Nhom1/" class="btn btn-outline-secondary">
                <i class="bi bi-globe me-1"></i> Trang chủ
            </a>
        </div>

        <!-- STAT CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="card admin-card stat-card p-3 border-start border-4 border-danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Đặc sản</span>
                            <h2 class="display-6 fw-bold mb-0 text-dark"><?= $tongDacSan ?></h2>
                        </div>
                        <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3 fs-3">
                            <i class="bi bi-basket2-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card admin-card stat-card p-3 border-start border-4 border-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Danh mục</span>
                            <h2 class="display-6 fw-bold mb-0 text-dark"><?= $tongDanhMuc ?></h2>
                        </div>
                        <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-3">
                            <i class="bi bi-tags-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card admin-card stat-card p-3 border-start border-4 border-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Cơ sở sản xuất</span>
                            <h2 class="display-6 fw-bold mb-0 text-dark"><?= $tongCoSo ?></h2>
                        </div>
                        <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 fs-3">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card admin-card stat-card p-3 border-start border-4 border-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Bài viết xuất bản</span>
                            <h2 class="display-6 fw-bold mb-0 text-dark"><?= $tongBaiViet ?></h2>
                        </div>
                        <div class="p-3 bg-info bg-opacity-10 text-info rounded-3 fs-3">
                            <i class="bi bi-journal-text"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card admin-card stat-card p-3 border-start border-4 border-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Người dùng</span>
                            <h2 class="display-6 fw-bold mb-0 text-dark"><?= $tongNguoiDung ?></h2>
                        </div>
                        <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 fs-3">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card admin-card stat-card p-3 border-start border-4 border-danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Liên hệ mới</span>
                            <h2 class="display-6 fw-bold mb-0 text-danger"><?= $lienHeMoi ?></h2>
                        </div>
                        <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3 fs-3">
                            <i class="bi bi-envelope-exclamation-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DANH SÁCH LIÊN HỆ GẦN ĐÂY -->
        <div class="card admin-card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h2 class="h5 fw-bold mb-0 text-dark">Liên hệ & Phản hồi gần đây</h2>
                <a href="/DuAnNgheCoBan_Nhom1/admin/lien-he/" class="btn btn-sm btn-outline-burgundy">
                    Xem tất cả
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Người gửi</th>
                                <th>Chủ đề</th>
                                <th>Trạng thái</th>
                                <th>Ngày gửi</th>
                                <th class="text-end pe-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($danhSachLienHeMoi)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Chưa có phản hồi liên hệ nào.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($danhSachLienHeMoi as $lienHe): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold text-dark">
                                            <?= htmlspecialchars((string)$lienHe['ho_ten']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars((string)($lienHe['chu_de'] ?: 'Không có chủ đề')) ?>
                                        </td>
                                        <td>
                                            <?= hienThiTrangThaiLienHe((string)$lienHe['trang_thai']) ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?= date('d/m/Y H:i', strtotime((string)$lienHe['ngay_gui'])) ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="/DuAnNgheCoBan_Nhom1/admin/lien-he/detail.php?id=<?= (int)$lienHe['id'] ?>" class="btn btn-sm btn-outline-primary">
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
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('toggleSidebarBtn');
    const sidebar = document.getElementById('adminSidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show-sidebar');
        });
    }
});
</script>
</body>
</html>