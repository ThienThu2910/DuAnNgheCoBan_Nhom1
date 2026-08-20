<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$pageTitle = 'Danh sách đặc sản Cà Mau';
$currentPage = 'dac-san';

$tuKhoa = trim($_GET['q'] ?? '');
$danhMucId = filter_input(INPUT_GET, 'danh_muc_id', FILTER_VALIDATE_INT);
$khuVuc = trim($_GET['khu_vuc'] ?? '');
$noiBat = isset($_GET['noi_bat']) ? 1 : null;

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
if (!$page || $page < 1) {
    $page = 1;
}

$limit = 9;
$offset = ($page - 1) * $limit;

/* 1. Lấy danh sách danh mục */
$stmtDanhMuc = $pdo->query('
    SELECT id, ten_danh_muc
    FROM danh_muc
    WHERE trang_thai = 1
    ORDER BY thu_tu ASC, ten_danh_muc ASC
');
$danhSachDanhMuc = $stmtDanhMuc->fetchAll();

/* 2. Điều kiện lọc dữ liệu */
$where = ' WHERE ds.trang_thai = 1 ';
$params = [];

if ($tuKhoa !== '') {
    // Sửa lỗi: Dùng LIKE trực tiếp trên chuỗi UTF-8 chuẩn xác
    $where .= ' AND (
        ds.ten_dac_san LIKE :tu_khoa1 
        OR dm.ten_danh_muc LIKE :tu_khoa2 
        OR ds.mo_ta_ngan LIKE :tu_khoa3 
        OR ds.mo_ta_chi_tiet LIKE :tu_khoa4
    ) ';
    $searchWildcard = '%' . $tuKhoa . '%';
    $params['tu_khoa1'] = $searchWildcard;
    $params['tu_khoa2'] = $searchWildcard;
    $params['tu_khoa3'] = $searchWildcard;
    $params['tu_khoa4'] = $searchWildcard;
}

if ($danhMucId) {
    $where .= ' AND ds.danh_muc_id = :danh_muc_id ';
    $params['danh_muc_id'] = $danhMucId;
}

if ($noiBat !== null) {
    $where .= ' AND ds.noi_bat = 1 ';
}

if ($khuVuc !== '') {
    $where .= ' AND cs.dia_chi LIKE :khu_vuc ';
    $params['khu_vuc'] = '%' . $khuVuc . '%';
}

/* 3. Đếm tổng số lượng kết quả */
$sqlDem = '
    SELECT COUNT(DISTINCT ds.id) AS tong
    FROM dac_san AS ds
    LEFT JOIN danh_muc AS dm ON dm.id = ds.danh_muc_id
    LEFT JOIN dac_san_co_so AS dscs ON dscs.dac_san_id = ds.id
    LEFT JOIN co_so_san_xuat AS cs ON cs.id = dscs.co_so_id
' . $where;

$stmtDem = $pdo->prepare($sqlDem);
$stmtDem->execute($params);
$tongDacSan = (int) ($stmtDem->fetch()['tong'] ?? 0);

$tongTrang = max(1, (int) ceil($tongDacSan / $limit));
if ($page > $tongTrang) {
    $page = $tongTrang;
    $offset = ($page - 1) * $limit;
}

/* 4. Lấy danh sách hiển thị theo phân trang */
$sql = '
    SELECT DISTINCT
        ds.id,
        ds.ten_dac_san,
        ds.slug,
        ds.mo_ta_ngan,
        ds.hinh_anh,
        ds.noi_bat,
        dm.ten_danh_muc
    FROM dac_san AS ds
    LEFT JOIN danh_muc AS dm ON dm.id = ds.danh_muc_id
    LEFT JOIN dac_san_co_so AS dscs ON dscs.dac_san_id = ds.id
    LEFT JOIN co_so_san_xuat AS cs ON cs.id = dscs.co_so_id
' . $where . '
    ORDER BY ds.noi_bat DESC, ds.id DESC
    LIMIT :limit OFFSET :offset
';

$stmt = $pdo->prepare($sql);
foreach ($params as $tenThamSo => $giaTri) {
    $stmt->bindValue(':' . $tenThamSo, $giaTri, $tenThamSo === 'danh_muc_id' ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$danhSachDacSan = $stmt->fetchAll();

function taoDuongDanTrang(
    int $soTrang,
    string $tuKhoa,
    ?int $danhMucId,
    string $khuVuc,
    ?int $noiBat
): string {
    $thamSo = ['page' => $soTrang];
    if ($tuKhoa !== '') $thamSo['q'] = $tuKhoa;
    if ($danhMucId) $thamSo['danh_muc_id'] = $danhMucId;
    if ($khuVuc !== '') $thamSo['khu_vuc'] = $khuVuc;
    if ($noiBat !== null) $thamSo['noi_bat'] = 1;

    return '?' . http_build_query($thamSo);
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<section class="specialty-banner">
    <div class="container text-center py-4">
        <h1 class="fw-bold mb-2 text-white display-5">Đặc Sản Cà Mau</h1>
        <p class="lead text-white-50 mb-0">Khám phá các sản vật đạt chuẩn OCOP và phong vị ẩm thực đất Mũi.</p>
    </div>
</section>

<main class="container py-5">
    <!-- Bộ lọc tìm kiếm -->
    <div class="card border-0 shadow-sm mb-5 rounded-4 overflow-hidden" style="background: var(--cm-card); border: 1px solid var(--cm-border) !important;">
        <div class="card-body p-4">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label for="q" class="form-label fw-semibold small text-muted">Từ khóa tìm kiếm</label>
                    <input 
                        type="text" 
                        id="q" 
                        name="q" 
                        class="form-control" 
                        placeholder="Tôm khô, mật ong, cua..." 
                        value="<?= htmlspecialchars($tuKhoa) ?>"
                    >
                </div>

                <div class="col-lg-3 col-md-6">
                    <label for="danh_muc_id" class="form-label fw-semibold small text-muted">Phân loại danh mục</label>
                    <select id="danh_muc_id" name="danh_muc_id" class="form-select">
                        <option value="">-- Tất cả danh mục --</option>
                        <?php foreach ($danhSachDanhMuc as $danhMuc): ?>
                            <option value="<?= (int)$danhMuc['id'] ?>" <?= $danhMucId === (int)$danhMuc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($danhMuc['ten_danh_muc']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label for="khu_vuc" class="form-label fw-semibold small text-muted">Khu vực địa bàn</label>
                    <select id="khu_vuc" name="khu_vuc" class="form-select">
                        <option value="">-- Toàn tỉnh Cà Mau --</option>
                        <option value="Năm Căn" <?= $khuVuc === 'Năm Căn' ? 'selected' : '' ?>>Năm Căn</option>
                        <option value="U Minh" <?= $khuVuc === 'U Minh' ? 'selected' : '' ?>>U Minh</option>
                        <option value="Ngọc Hiển" <?= $khuVuc === 'Ngọc Hiển' ? 'selected' : '' ?>>Ngọc Hiển (Đất Mũi, Rạch Gốc)</option>
                        <option value="Trần Văn Thời" <?= $khuVuc === 'Trần Văn Thời' ? 'selected' : '' ?>>Trần Văn Thời</option>
                        <option value="Cái Nước" <?= $khuVuc === 'Cái Nước' ? 'selected' : '' ?>>Cái Nước</option>
                        <option value="Cà Mau" <?= $khuVuc === 'Cà Mau' ? 'selected' : '' ?>>TP. Cà Mau</option>
                    </select>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-cm-primary flex-grow-1">
                            <i class="bi bi-funnel"></i> Lọc dữ liệu
                        </button>
                        <a href="dac-san.php" class="btn btn-outline-secondary px-3" title="Xóa bộ lọc">Xóa</a>
                    </div>
                </div>

                <div class="col-12 mt-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="noi_bat" value="1" id="noi_bat" <?= $noiBat !== null ? 'checked' : '' ?>>
                        <label class="form-check-label small text-muted" for="noi_bat">Chỉ hiển thị các đặc sản tiêu biểu (Nổi bật)</label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tiêu đề số lượng kết quả -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-1">Danh Sách Đặc Sản</h2>
            <?php if ($tuKhoa !== ''): ?>
                <p class="small text-muted mb-0">Kết quả tìm kiếm cho từ khóa: <strong>"<?= htmlspecialchars($tuKhoa) ?>"</strong></p>
            <?php endif; ?>
        </div>
        <span class="badge" style="background: var(--cm-red); color: #fff; padding: 8px 16px; border-radius: 20px; font-size: 13px;">
            <?= $tongDacSan ?> sản phẩm
        </span>
    </div>

    <?php if (empty($danhSachDacSan)): ?>
        <div class="alert alert-warning text-center py-5 rounded-4 border-0 shadow-sm" style="background: #fff8e6;">
            <i class="bi bi-search fs-1 d-block mb-3 text-warning"></i>
            <h5 class="fw-bold text-dark">Không tìm thấy đặc sản nào</h5>
            <p class="text-muted small mb-3">Vui lòng thử lại với từ khóa khác hoặc xóa bớt tiêu chí lọc.</p>
            <a href="dac-san.php" class="btn-cm-primary btn-sm">Xem tất cả đặc sản</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($danhSachDacSan as $dacSan): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="specialty-card">
                        <div class="card-img-wrapper">
                            <?php if (!empty($dacSan['hinh_anh'])): ?>
                                <img 
                                    src="<?= htmlspecialchars($baseUrl) ?>/assets/uploads/dac-san/<?= htmlspecialchars($dacSan['hinh_anh']) ?>" 
                                    alt="<?= htmlspecialchars($dacSan['ten_dac_san']) ?>"
                                    class="specialty-image"
                                >
                            <?php else: ?>
                                <div class="specialty-no-image"><i class="bi bi-image fs-2"></i></div>
                            <?php endif; ?>

                            <?php if ((int)$dacSan['noi_bat'] === 1): ?>
                                <span class="badge-floating-top">
                                    <i class="bi bi-star-fill me-1"></i> Nổi bật
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body-custom">
                            <div>
                                <span class="badge-category">
                                    <?= htmlspecialchars($dacSan['ten_danh_muc'] ?? 'Đặc sản Cà Mau') ?>
                                </span>
                            </div>

                            <h3 class="h5 fw-bold mb-2 text-dark"><?= htmlspecialchars($dacSan['ten_dac_san']) ?></h3>
                            <p class="limited-text">
                                <?= htmlspecialchars($dacSan['mo_ta_ngan'] ?: 'Sản vật thơm ngon nức tiếng của vùng đất cuối trời Nam.') ?>
                            </p>

                            <a href="<?= htmlspecialchars($baseUrl) ?>/chi-tiet-dac-san.php?id=<?= (int)$dacSan['id'] ?>" class="btn-cm-card mt-auto">
                                <span>Xem chi tiết</span> <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Phân trang -->
        <?php if ($tongTrang > 1): ?>
            <nav class="mt-5" aria-label="Phân trang đặc sản">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars(taoDuongDanTrang(max(1, $page - 1), $tuKhoa, $danhMucId ?: null, $khuVuc, $noiBat)) ?>">« Trước</a>
                    </li>

                    <?php for ($i = 1; $i <= $tongTrang; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars(taoDuongDanTrang($i, $tuKhoa, $danhMucId ?: null, $khuVuc, $noiBat)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $tongTrang ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars(taoDuongDanTrang(min($tongTrang, $page + 1), $tuKhoa, $danhMucId ?: null, $khuVuc, $noiBat)) ?>">Sau »</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>