<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

// Thống kê dữ liệu tổng quan
$tongDacSan = (int)$pdo->query('SELECT COUNT(*) FROM dac_san')->fetchColumn();
$tongDanhMuc = (int)$pdo->query('SELECT COUNT(*) FROM danh_muc')->fetchColumn();
$tongCoSo = (int)$pdo->query('SELECT COUNT(*) FROM co_so_san_xuat')->fetchColumn();
$tongBaiViet = (int)$pdo->query('SELECT COUNT(*) FROM bai_viet')->fetchColumn();
$tongLienHeMoi = (int)$pdo->query("SELECT COUNT(*) FROM lien_he WHERE trang_thai = 'moi'")->fetchColumn();

// Lấy 5 đặc sản mới nhất
$stmtDacSanMoi = $pdo->query('
    SELECT ds.id, ds.ten_dac_san, ds.hinh_anh, ds.noi_bat, ds.trang_thai, dm.ten_danh_muc 
    FROM dac_san ds 
    LEFT JOIN danh_muc dm ON ds.danh_muc_id = dm.id 
    ORDER BY ds.id DESC 
    LIMIT 5
');
$danhSachDacSanMoi = $stmtDacSanMoi->fetchAll();

// Lấy 5 liên hệ mới nhất
$stmtLienHeMoi = $pdo->query("
    SELECT id, ho_ten, chu_de, ngay_gui, trang_thai 
    FROM lien_he 
    ORDER BY id DESC 
    LIMIT 5
");
$danhSachLienHeMoi = $stmtLienHeMoi->fetchAll();

$adminPage = 'tong-quan';
$pageTitle = 'Bảng điều khiển tổng quan';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Tổng quan hệ thống</h1>
        <p class="text-muted mb-0">Theo dõi số liệu và hoạt động của trang thông tin Đặc sản Cà Mau.</p>
    </div>
    <a href="/DuAnNgheCoBan_Nhom1/" target="_blank" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-box-arrow-up-right me-1"></i> Xem trang chủ website
    </a>
</div>

<!-- 1. HÀNG THẺ THỐNG KÊ -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card admin-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-3 bg-danger bg-opacity-10 text-danger fs-3">
                    <i class="bi bi-basket2-fill"></i>
                </div>
                <div>
                    <h6 class="text-muted small mb-1 fw-semibold text-uppercase">Đặc sản</h6>
                    <h3 class="fw-bold mb-0 text-dark"><?= $tongDacSan ?></h3>
                </div>
            </div>
            <a href="dac-san/" class="small text-danger fw-semibold mt-3 text-decoration-none d-flex align-items-center">
                Quản lý đặc sản <i class="bi bi-chevron-right ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card admin-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-3 bg-primary bg-opacity-10 text-primary fs-3">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <div>
                    <h6 class="text-muted small mb-1 fw-semibold text-uppercase">Cơ sở / Điểm bán</h6>
                    <h3 class="fw-bold mb-0 text-dark"><?= $tongCoSo ?></h3>
                </div>
            </div>
            <a href="co-so/" class="small text-primary fw-semibold mt-3 text-decoration-none d-flex align-items-center">
                Quản lý cơ sở <i class="bi bi-chevron-right ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card admin-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-3 bg-success bg-opacity-10 text-success fs-3">
                    <i class="bi bi-journal-text"></i>
                </div>
                <div>
                    <h6 class="text-muted small mb-1 fw-semibold text-uppercase">Bài viết / Di sản</h6>
                    <h3 class="fw-bold mb-0 text-dark"><?= $tongBaiViet ?></h3>
                </div>
            </div>
            <a href="bai-viet/" class="small text-success fw-semibold mt-3 text-decoration-none d-flex align-items-center">
                Quản lý bài viết <i class="bi bi-chevron-right ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card admin-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-3 bg-warning bg-opacity-10 text-warning fs-3">
                    <i class="bi bi-envelope-fill"></i>
                </div>
                <div>
                    <h6 class="text-muted small mb-1 fw-semibold text-uppercase">Liên hệ mới</h6>
                    <h3 class="fw-bold mb-0 text-dark"><?= $tongLienHeMoi ?></h3>
                </div>
            </div>
            <a href="lien-he/" class="small text-warning fw-semibold mt-3 text-decoration-none d-flex align-items-center">
                Xem phản hồi <i class="bi bi-chevron-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- 2. BẢNG DỮ LIỆU MỚI NHẤT -->
<div class="row g-4">
    <!-- Đặc sản mới cập nhật -->
    <div class="col-lg-7">
        <div class="card admin-card h-100">
            <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-stars text-danger me-2"></i>Đặc sản mới thêm gần đây</h5>
                <a href="dac-san/create.php" class="btn btn-sm btn-success py-1 px-2"><i class="bi bi-plus-lg"></i> Thêm mới</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3" style="width: 70px;">Ảnh</th>
                                <th>Tên đặc sản</th>
                                <th>Danh mục</th>
                                <th class="text-end pe-3">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($danhSachDacSanMoi)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">Chưa có đặc sản nào.</td></tr>
                            <?php else: ?>
                                <?php foreach ($danhSachDacSanMoi as $ds): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <?php if (!empty($ds['hinh_anh'])): ?>
                                                <img src="/DuAnNgheCoBan_Nhom1/assets/uploads/dac-san/<?= htmlspecialchars($ds['hinh_anh']) ?>" class="rounded border" style="width: 48px; height: 38px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded text-center small text-muted d-flex align-items-center justify-content-center" style="width: 48px; height: 38px;"><i class="bi bi-image"></i></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="dac-san/edit.php?id=<?= (int)$ds['id'] ?>" class="text-dark fw-semibold text-decoration-none">
                                                <?= htmlspecialchars($ds['ten_dac_san']) ?>
                                            </a>
                                            <?php if ((int)$ds['noi_bat'] === 1): ?>
                                                <span class="badge bg-danger ms-1" style="font-size: 10px;">Nổi bật</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($ds['ten_danh_muc'] ?? 'Chưa phân loại') ?></span></td>
                                        <td class="text-end pe-3">
                                            <span class="badge <?= (int)$ds['trang_thai'] === 1 ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= (int)$ds['trang_thai'] === 1 ? 'Hiển thị' : 'Ẩn' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Phản hồi liên hệ mới nhất -->
    <div class="col-lg-5">
        <div class="card admin-card h-100">
            <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-chat-left-dots-fill text-warning me-2"></i>Liên hệ & Góp ý mới</h5>
                <a href="lien-he/" class="btn btn-sm btn-outline-secondary py-1 px-2">Xem tất cả</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (empty($danhSachLienHeMoi)): ?>
                        <div class="text-center text-muted py-4">Chưa có liên hệ nào từ khách truy cập.</div>
                    <?php else: ?>
                        <?php foreach ($danhSachLienHeMoi as $lh): ?>
                            <a href="lien-he/detail.php?id=<?= (int)$lh['id'] ?>" class="list-group-item list-group-item-action p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-dark"><?= htmlspecialchars($lh['ho_ten']) ?></strong>
                                    <span class="badge <?= $lh['trang_thai'] === 'moi' ? 'bg-danger' : 'bg-light text-dark border' ?>">
                                        <?= $lh['trang_thai'] === 'moi' ? 'Mới' : 'Đã xem' ?>
                                    </span>
                                </div>
                                <p class="small text-muted mb-1 text-truncate"><?= htmlspecialchars($lh['chu_de'] ?: 'Không có tiêu đề') ?></p>
                                <small class="text-muted" style="font-size: 11px;"><i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($lh['ngay_gui'])) ?></small>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>