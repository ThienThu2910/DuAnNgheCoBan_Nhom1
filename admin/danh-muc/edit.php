<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';

$thongBao = $_SESSION['success'] ?? '';
$loi = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục - Quản trị Cà Mau</title>
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
        body { margin: 0; background-color: var(--admin-bg); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #2b2b2b; }
        .admin-sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0; z-index: 1040; background: linear-gradient(180deg, var(--admin-red-dark) 0%, var(--admin-red) 100%); color: #fff; overflow-y: auto; box-shadow: 4px 0 20px rgba(0,0,0,0.12); transition: transform 0.3s ease; }
        .sidebar-brand { height: var(--topbar-height); display: flex; align-items: center; padding: 0 22px; font-size: 17px; font-weight: 700; letter-spacing: .5px; color: #fff; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.12); }
        .sidebar-menu { padding: 16px 12px; list-style: none; margin: 0; }
        .sidebar-menu .menu-header { font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; color: rgba(255,255,255,0.45); padding: 14px 12px 6px; }
        .sidebar-menu .nav-item { margin-bottom: 4px; }
        .sidebar-menu .nav-link { display: flex; align-items: center; gap: 12px; padding: 10px 14px; color: rgba(255,255,255,0.82); font-size: 14px; font-weight: 500; border-radius: 8px; text-decoration: none; transition: all 0.2s ease; }
        .sidebar-menu .nav-link:hover { color: #fff; background: rgba(255,255,255,0.12); transform: translateX(4px); }
        .sidebar-menu .nav-link.active { color: #fff; background: rgba(255,255,255,0.22); font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .admin-main-wrapper { margin-left: var(--sidebar-width); min-height: 100vh; display: flex; flex-direction: column; }
        .admin-topbar { height: var(--topbar-height); background: #fff; border-bottom: 1px solid var(--admin-border); position: sticky; top: 0; z-index: 1030; display: flex; align-items: center; justify-content: space-between; padding: 0 28px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
        .admin-content { padding: 28px; flex-grow: 1; }
        .admin-card { border: 0; border-radius: 12px; background: var(--admin-card-bg); box-shadow: 0 4px 16px rgba(0,0,0,0.04); }
        .btn-burgundy { background-color: var(--admin-red) !important; border-color: var(--admin-red) !important; color: #fff !important; }
        .btn-burgundy:hover { background-color: var(--admin-red-hover) !important; border-color: var(--admin-red-hover) !important; }
        .table thead th { background-color: #faf6f6; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: #555; border-bottom: 2px solid var(--admin-border); }
        @media (max-width: 991.98px) { .admin-sidebar { transform: translateX(-100%); } .admin-sidebar.show-sidebar { transform: translateX(0); } .admin-main-wrapper { margin-left: 0; } }
    </style>
</head>
<body>

<aside class="admin-sidebar" id="adminSidebar">
    <a href="/DuAnNgheCoBan_Nhom1/admin/index.php" class="sidebar-brand">
        <i class="bi bi-shield-lock-fill me-2 fs-5"></i> QUẢN TRỊ CÀ MAU
    </a>
    <ul class="sidebar-menu">
        <li class="menu-header">Bảng điều khiển</li>
        <li class="nav-item"><a href="/DuAnNgheCoBan_Nhom1/admin/index.php" class="nav-link"><i class="bi bi-grid-1x2-fill"></i> Tổng quan</a></li>
        <li class="menu-header">Quản lý nội dung</li>
        <li class="nav-item"><a href="/DuAnNgheCoBan_Nhom1/admin/dac-san/" class="nav-link"><i class="bi bi-basket2-fill"></i> Quản lý đặc sản</a></li>
        <li class="nav-item"><a href="/DuAnNgheCoBan_Nhom1/admin/danh-muc/" class="nav-link active"><i class="bi bi-tags-fill"></i> Quản lý danh mục</a></li>
        <li class="nav-item"><a href="/DuAnNgheCoBan_Nhom1/admin/co-so/" class="nav-link"><i class="bi bi-geo-alt-fill"></i> Quản lý cơ sở</a></li>
        <li class="nav-item"><a href="/DuAnNgheCoBan_Nhom1/admin/bai-viet/" class="nav-link"><i class="bi bi-journal-text"></i> Quản lý bài viết</a></li>
        <li class="nav-item"><a href="/DuAnNgheCoBan_Nhom1/admin/lien-he/" class="nav-link"><i class="bi bi-envelope-fill"></i> Quản lý liên hệ</a></li>
        <li class="menu-header">Hệ thống</li>
        <li class="nav-item"><a href="/DuAnNgheCoBan_Nhom1/" target="_blank" class="nav-link"><i class="bi bi-box-arrow-up-right"></i> Xem trang chủ</a></li>
        <li class="nav-item"><a href="/DuAnNgheCoBan_Nhom1/logout.php" class="nav-link text-danger-emphasis"><i class="bi bi-box-arrow-left"></i> Đăng xuất</a></li>
    </ul>
</aside>

<div class="admin-main-wrapper">
    <header class="admin-topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-secondary d-lg-none" id="toggleSidebarBtn" type="button"><i class="bi bi-list fs-5"></i></button>
            <span class="fw-semibold text-secondary d-none d-md-inline">Hệ thống quản trị đặc sản Cà Mau</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="small fw-semibold text-dark"><i class="bi bi-person-circle fs-5 me-1 text-secondary align-middle"></i> <?= htmlspecialchars((string)($_SESSION['admin_name'] ?? 'Quản trị viên')) ?></span>
            <a href="/DuAnNgheCoBan_Nhom1/logout.php" class="btn btn-sm btn-outline-danger px-3">Đăng xuất</a>
        </div>
    </header>

    <main class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Quản lý danh mục</h1>
                <p class="text-muted mb-0">Quản lý các nhóm phân loại đặc sản trên website.</p>
            </div>
            <a href="create.php" class="btn btn-burgundy"><i class="bi bi-plus-lg me-1"></i> Thêm danh mục</a>
        </div>

        <?php if ($thongBao !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($thongBao) ?></div><?php endif; ?>
        <?php if ($loi !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($loi) ?></div><?php endif; ?>

        <div class="card admin-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 70px;">STT</th>
                                <th>Tên danh mục</th>
                                <th>Đường dẫn</th>
                                <th style="width: 100px;">Thứ tự</th>
                                <th style="width: 130px;">Trạng thái</th>
                                <th class="text-end pe-4" style="width: 180px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($danhMuc)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Chưa có danh mục nào.</td></tr>
                            <?php else: ?>
                                <?php foreach ($danhMuc as $index => $item): ?>
                                    <tr>
                                        <td class="ps-4 text-muted"><?= $index + 1 ?></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($item['ten_danh_muc']) ?></td>
                                        <td><code><?= htmlspecialchars($item['slug']) ?></code></td>
                                        <td><?= (int) $item['thu_tu'] ?></td>
                                        <td>
                                            <?php if ((int) $item['trang_thai'] === 1): ?>
                                                <span class="badge bg-success">Hiển thị</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Đang ẩn</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="edit.php?id=<?= (int) $item['id'] ?>" class="btn btn-warning btn-sm me-1">Sửa</a>
                                            <form action="delete.php" method="post" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?');">
                                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
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
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('toggleSidebarBtn');
    const sidebar = document.getElementById('adminSidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () { sidebar.classList.toggle('show-sidebar'); });
    }
});
</script>
</body>
</html>