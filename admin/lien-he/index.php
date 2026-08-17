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
    $sql .= ' AND (ho_ten LIKE :q1 OR email LIKE :q2 OR chu_de LIKE :q3 OR noi_dung LIKE :q4)';
    $giaTriTimKiem = '%' . $tuKhoa . '%';
    $params['q1'] = $giaTriTimKiem;
    $params['q2'] = $giaTriTimKiem;
    $params['q3'] = $giaTriTimKiem;
    $params['q4'] = $giaTriTimKiem;
}

if (in_array($trangThai, ['moi', 'da_xem', 'da_phan_hoi'], true)) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý liên hệ - Quản trị Cà Mau</title>
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
        <li class="nav-item"><a href="/DuAnNgheCoBan_Nhom1/admin/danh-muc/" class="nav-link"><i class="bi bi-tags-fill"></i> Quản lý danh mục</a></li>
        <li class="nav-item"><a href="/DuAnNgheCoBan_Nhom1/admin/co-so/" class="nav-link"><i class="bi bi-geo-alt-fill"></i> Quản lý cơ sở</a></li>
        <li class="nav-item"><a href="/DuAnNgheCoBan_Nhom1/admin/bai-viet/" class="nav-link"><i class="bi bi-journal-text"></i> Quản lý bài viết</a></li>
        <li class="nav-item"><a href="/DuAnNgheCoBan_Nhom1/admin/lien-he/" class="nav-link active"><i class="bi bi-envelope-fill"></i> Quản lý liên hệ</a></li>
        <li class="menu-header">Hệ thống</li>
        <!-- Đã bỏ target="_blank" -->
        <li class="nav-item"><a href="/DuAnNgheCoBan_Nhom1/" class="nav-link"><i class="bi bi-box-arrow-up-right"></i> Xem trang chủ</a></li>
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
                <h1 class="h3 fw-bold mb-1">Quản lý liên hệ</h1>
                <p class="text-muted mb-0">Xem, phản hồi và xử lý ý kiến đóng góp từ khách truy cập.</p>
            </div>
        </div>

        <?php if ($thongBao !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($thongBao) ?></div><?php endif; ?>
        <?php if ($loi !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($loi) ?></div><?php endif; ?>

        <div class="card admin-card mb-4">
            <div class="card-body p-3">
                <form method="get" class="row g-3">
                    <div class="col-md-6">
                        <input type="text" id="q" name="q" class="form-control" placeholder="Tìm tên, email, chủ đề, nội dung..." value="<?= htmlspecialchars($tuKhoa) ?>">
                    </div>
                    <div class="col-md-4">
                        <select id="trang_thai" name="trang_thai" class="form-select">
                            <option value="">Tất cả trạng thái</option>
                            <option value="moi" <?= $trangThai === 'moi' ? 'selected' : '' ?>>Mới</option>
                            <option value="da_xem" <?= $trangThai === 'da_xem' ? 'selected' : '' ?>>Đã xem</option>
                            <option value="da_phan_hoi" <?= $trangThai === 'da_phan_hoi' ? 'selected' : '' ?>>Đã phản hồi</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-burgundy flex-grow-1">Lọc</button>
                        <a href="index.php" class="btn btn-secondary px-3">Xóa</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card admin-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 65px;">STT</th>
                                <th>Người gửi</th>
                                <th>Chủ đề & Nội dung</th>
                                <th style="width: 140px;">Trạng thái</th>
                                <th style="width: 160px;">Ngày gửi</th>
                                <th class="text-end pe-4" style="width: 160px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($danhSachLienHe)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Chưa có thông tin liên hệ.</td></tr>
                            <?php else: ?>
                                <?php foreach ($danhSachLienHe as $index => $lienHe): ?>
                                    <tr>
                                        <td class="ps-4 text-muted"><?= $index + 1 ?></td>
                                        <td>
                                            <strong class="text-dark"><?= htmlspecialchars($lienHe['ho_ten']) ?></strong>
                                            <?php if (!empty($lienHe['email'])): ?>
                                                <div class="small text-muted"><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($lienHe['email']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($lienHe['chu_de'] ?: 'Không có chủ đề') ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars(mb_strimwidth($lienHe['noi_dung'], 0, 80, '...')) ?></div>
                                        </td>
                                        <td><?= hienThiTrangThai($lienHe['trang_thai']) ?></td>
                                        <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($lienHe['ngay_gui'])) ?></td>
                                        <td class="text-end pe-4">
                                            <a href="detail.php?id=<?= (int) $lienHe['id'] ?>" class="btn btn-outline-primary btn-sm me-1">Xem</a>
                                            <form method="post" action="delete.php" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa liên hệ này?');">
                                                <input type="hidden" name="id" value="<?= (int) $lienHe['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_admin_contact']) ?>">
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