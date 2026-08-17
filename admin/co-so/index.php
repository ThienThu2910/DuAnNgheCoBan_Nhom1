<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$thongBao = $_SESSION['success'] ?? '';
$loi = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$tuKhoa = trim($_GET['q'] ?? '');

$sql = '
    SELECT cs.*,
        (SELECT COUNT(*) FROM dac_san_co_so AS dscs WHERE dscs.co_so_id = cs.id) AS so_dac_san
    FROM co_so_san_xuat AS cs
    WHERE 1 = 1
';
$params = [];

if ($tuKhoa !== '') {
    $sql .= ' AND (cs.ten_co_so LIKE :tu_khoa OR cs.dia_chi LIKE :tu_khoa)';
    $params['tu_khoa'] = '%' . $tuKhoa . '%';
}

$sql .= ' ORDER BY cs.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$danhSachCoSo = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý cơ sở sản xuất - Quản trị Cà Mau</title>
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
        .facility-image { width: 80px; height: 60px; object-fit: cover; border-radius: 6px; }
        .no-image { width: 80px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 6px; background-color: #e9ecef; color: #6c757d; font-size: 11px; }
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
        <li class="nav-item"><a href="/DuAnNgheCoBan_Nhom1/admin/co-so/" class="nav-link active"><i class="bi bi-geo-alt-fill"></i> Quản lý cơ sở</a></li>
        <li class="nav-item"><a href="/DuAnNgheCoBan_Nhom1/admin/bai-viet/" class="nav-link"><i class="bi bi-journal-text"></i> Quản lý bài viết</a></li>
        <li class="nav-item"><a href="/DuAnNgheCoBan_Nhom1/admin/lien-he/" class="nav-link"><i class="bi bi-envelope-fill"></i> Quản lý liên hệ</a></li>
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
                <h1 class="h3 fw-bold mb-1">Quản lý cơ sở sản xuất</h1>
                <p class="text-muted mb-0">Quản lý địa chỉ, liên hệ và vị trí bản đồ của các cơ sở đặc sản.</p>
            </div>
            <a href="create.php" class="btn btn-burgundy"><i class="bi bi-plus-lg me-1"></i> Thêm cơ sở</a>
        </div>

        <?php if ($thongBao !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($thongBao) ?></div><?php endif; ?>
        <?php if ($loi !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($loi) ?></div><?php endif; ?>

        <div class="card admin-card mb-4">
            <div class="card-body p-3">
                <form method="get" class="row g-3">
                    <div class="col-md-9">
                        <input type="text" id="q" name="q" class="form-control" placeholder="Nhập tên cơ sở hoặc địa chỉ..." value="<?= htmlspecialchars($tuKhoa) ?>">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-burgundy flex-grow-1">Tìm kiếm</button>
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
                                <th style="width: 100px;">Hình ảnh</th>
                                <th>Tên cơ sở</th>
                                <th>Địa chỉ</th>
                                <th style="width: 100px;">Đặc sản</th>
                                <th style="width: 120px;">Trạng thái</th>
                                <th class="text-end pe-4" style="width: 200px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($danhSachCoSo)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">Chưa có cơ sở sản xuất nào.</td></tr>
                            <?php else: ?>
                                <?php foreach ($danhSachCoSo as $index => $coSo): ?>
                                    <tr>
                                        <td class="ps-4 text-muted"><?= $index + 1 ?></td>
                                        <td>
                                            <?php if (!empty($coSo['hinh_anh'])): ?>
                                                <img src="/DuAnNgheCoBan_Nhom1/assets/uploads/co-so/<?= htmlspecialchars($coSo['hinh_anh']) ?>" class="facility-image">
                                            <?php else: ?>
                                                <div class="no-image">Chưa có ảnh</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong class="text-dark"><?= htmlspecialchars($coSo['ten_co_so']) ?></strong>
                                            <?php if (!empty($coSo['so_dien_thoai'])): ?>
                                                <div class="small text-muted mt-1"><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($coSo['so_dien_thoai']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-secondary"><?= htmlspecialchars($coSo['dia_chi']) ?></td>
                                        <td><span class="badge bg-info text-dark"><?= (int) $coSo['so_dac_san'] ?> sản phẩm</span></td>
                                        <td>
                                            <span class="badge <?= (int)$coSo['trang_thai'] === 1 ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= (int)$coSo['trang_thai'] === 1 ? 'Hiển thị' : 'Đang ẩn' ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="<?= htmlspecialchars(taoGoogleMapsUrl($coSo)) ?>" target="_blank" class="btn btn-outline-primary btn-sm me-1">Bản đồ</a>
                                            <a href="edit.php?id=<?= (int)$coSo['id'] ?>" class="btn btn-warning btn-sm me-1">Sửa</a>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="xacNhanXoa(<?= (int)$coSo['id'] ?>, '<?= htmlspecialchars($coSo['ten_co_so'], ENT_QUOTES) ?>')">Xóa</button>
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

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Xác nhận xóa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Bạn có chắc chắn muốn xóa cơ sở <strong id="tenCoSoXoa"></strong> không? Thao tác này sẽ xóa kèm toàn bộ liên kết đặc sản của cơ sở.
            </div>
            <div class="modal-footer">
                <form method="post" action="delete.php">
                    <input type="hidden" name="id" id="idCoSoXoa" value="">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xóa ngay</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function xacNhanXoa(id, ten) {
    document.getElementById('idCoSoXoa').value = id;
    document.getElementById('tenCoSoXoa').innerText = ten;
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
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