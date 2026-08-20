<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error'] = 'Mã danh mục không hợp lệ.';
    header('Location: index.php');
    exit;
}

// 1. Lấy thông tin danh mục hiện tại
$stmt = $pdo->prepare('SELECT * FROM danh_muc WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$danhMuc = $stmt->fetch();

if (!$danhMuc) {
    $_SESSION['error'] = 'Không tìm thấy danh mục cần sửa.';
    header('Location: index.php');
    exit;
}

$loi = [];
$tenDanhMuc = $danhMuc['ten_danh_muc'];
$slug = $danhMuc['slug'];
$moTa = $danhMuc['mo_ta'] ?? '';
$thuTu = (int)$danhMuc['thu_tu'];
$trangThai = (int)$danhMuc['trang_thai'];

// 2. Xử lý lưu form cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDanhMuc = trim($_POST['ten_danh_muc'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $moTa = trim($_POST['mo_ta'] ?? '');
    $thuTu = (int)($_POST['thu_tu'] ?? 0);
    $trangThai = isset($_POST['trang_thai']) ? 1 : 0;

    if ($tenDanhMuc === '') {
        $loi[] = 'Vui lòng nhập tên danh mục.';
    }

    if ($slug === '') {
        $slug = function_exists('taoSlug') ? taoSlug($tenDanhMuc) : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $tenDanhMuc), '-'));
    } else {
        $slug = function_exists('taoSlug') ? taoSlug($slug) : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug), '-'));
    }

    if ($slug === '') {
        $loi[] = 'Đường dẫn danh mục không hợp lệ.';
    }

    // Kiểm tra trùng lặp tên hoặc slug với các danh mục khác
    $kiemTra = $pdo->prepare('SELECT id FROM danh_muc WHERE (ten_danh_muc = :ten OR slug = :slug) AND id <> :id LIMIT 1');
    $kiemTra->execute([
        'ten' => $tenDanhMuc,
        'slug' => $slug,
        'id' => $id
    ]);

    if ($kiemTra->fetch()) {
        $loi[] = 'Tên danh mục hoặc đường dẫn đã tồn tại trên hệ thống.';
    }

    if (empty($loi)) {
        try {
            $update = $pdo->prepare('
                UPDATE danh_muc 
                SET ten_danh_muc = :ten, 
                    slug = :slug, 
                    mo_ta = :mo_ta, 
                    thu_tu = :thu_tu, 
                    trang_thai = :trang_thai 
                WHERE id = :id
            ');
            $update->execute([
                'ten' => $tenDanhMuc,
                'slug' => $slug,
                'mo_ta' => $moTa !== '' ? $moTa : null,
                'thu_tu' => $thuTu,
                'trang_thai' => $trangThai,
                'id' => $id
            ]);

            $_SESSION['success'] = 'Cập nhật danh mục thành công!';
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $loi[] = 'Không thể cập nhật danh mục: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa danh mục - Quản trị Cà Mau</title>
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
                <h1 class="h3 fw-bold mb-1">Chỉnh sửa danh mục</h1>
                <p class="text-muted mb-0">Cập nhật thông tin phân loại đặc sản trên hệ thống.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Danh sách danh mục
            </a>
        </div>

        <?php if (!empty($loi)): ?>
            <div class="alert alert-danger mb-4">
                <ul class="mb-0 ps-3">
                    <?php foreach ($loi as $itemLoi): ?>
                        <li><?= htmlspecialchars($itemLoi) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card admin-card" style="max-width: 800px;">
            <div class="card-body p-4 p-md-5">
                <form method="post">
                    <div class="mb-3">
                        <label for="ten_danh_muc" class="form-label fw-semibold">Tên danh mục <span class="text-danger">*</span></label>
                        <input 
                            type="text" 
                            id="ten_danh_muc" 
                            name="ten_danh_muc" 
                            class="form-control" 
                            value="<?= htmlspecialchars($tenDanhMuc) ?>" 
                            placeholder="Ví dụ: Thủy hải sản, Mắm & món truyền thống..." 
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label fw-semibold">Đường dẫn (Slug)</label>
                        <input 
                            type="text" 
                            id="slug" 
                            name="slug" 
                            class="form-control" 
                            value="<?= htmlspecialchars($slug) ?>" 
                            placeholder="Để trống để tự động tạo slug chuẩn SEO"
                        >
                        <div class="form-text">Ví dụ: thuy-hai-san, san-vat-u-minh</div>
                    </div>

                    <div class="mb-3">
                        <label for="mo_ta" class="form-label fw-semibold">Mô tả danh mục</label>
                        <textarea 
                            id="mo_ta" 
                            name="mo_ta" 
                            class="form-control" 
                            rows="3" 
                            placeholder="Tóm tắt giới thiệu về nhóm danh mục này..."
                        ><?= htmlspecialchars($moTa) ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="thu_tu" class="form-label fw-semibold">Thứ tự hiển thị</label>
                        <input 
                            type="number" 
                            id="thu_tu" 
                            name="thu_tu" 
                            class="form-control" 
                            value="<?= $thuTu ?>" 
                            min="0" 
                            style="max-width: 160px;"
                        >
                    </div>

                    <div class="form-check mb-4 p-3 bg-light rounded border">
                        <input 
                            class="form-check-input ms-0 me-2" 
                            type="checkbox" 
                            id="trang_thai" 
                            name="trang_thai" 
                            value="1" 
                            <?= $trangThai === 1 ? 'checked' : '' ?>
                        >
                        <label class="form-check-label fw-semibold" for="trang_thai">
                            Hiển thị công khai ra ngoài website
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-burgundy px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Lưu thay đổi
                        </button>
                        <a href="index.php" class="btn btn-secondary px-3">Hủy</a>
                    </div>
                </form>
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