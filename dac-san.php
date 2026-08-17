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

/* Lấy danh sách danh mục đang hiển thị */
$stmtDanhMuc = $pdo->query('
    SELECT id, ten_danh_muc
    FROM danh_muc
    WHERE trang_thai = 1
    ORDER BY thu_tu ASC, ten_danh_muc ASC
');
$danhSachDanhMuc = $stmtDanhMuc->fetchAll();

/* Điều kiện tìm kiếm và lọc kết hợp */
$where = ' WHERE ds.trang_thai = 1 ';
$params = [];

if ($tuKhoa !== '') {
    $where .= ' AND (ds.ten_dac_san LIKE :tu_khoa1 OR ds.mo_ta_ngan LIKE :tu_khoa2) ';
    $params['tu_khoa1'] = '%' . $tuKhoa . '%';
    $params['tu_khoa2'] = '%' . $tuKhoa . '%';
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

/* Đếm tổng số đặc sản */
$sqlDem = '
    SELECT COUNT(DISTINCT ds.id) AS tong
    FROM dac_san AS ds
    LEFT JOIN dac_san_co_so AS dscs ON dscs.dac_san_id = ds.id
    LEFT JOIN co_so_san_xuat AS cs ON cs.id = dscs.co_so_id
' . $where;

$stmtDem = $pdo->prepare($sqlDem);
$stmtDem->execute($params);
$ketQuaDem = $stmtDem->fetch();
$tongDacSan = (int) ($ketQuaDem['tong'] ?? 0);

$tongTrang = max(1, (int) ceil($tongDacSan / $limit));
if ($page > $tongTrang) {
    $page = $tongTrang;
    $offset = ($page - 1) * $limit;
}

/* Lấy danh sách đặc sản */
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

/* Tạo đường dẫn phân trang giữ nguyên toàn bộ bộ lọc */
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

<style>
    .specialty-banner {
        padding: 60px 0;
        color: #ffffff;
        text-align: center;
        background: linear-gradient(rgba(18, 74, 52, 0.82), rgba(18, 74, 52, 0.82)), url("<?= htmlspecialchars($baseUrl) ?>/assets/images/banner-ca-mau.jpg") center / cover no-repeat;
    }
    .specialty-card { overflow: hidden; border: 0; border-radius: 12px; transition: transform 0.25s ease, box-shadow 0.25s ease; }
    .specialty-card:hover { transform: translateY(-5px); box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12); }
    .specialty-card img, .specialty-no-image { width: 100%; height: 220px; object-fit: cover; }
    .specialty-no-image { display: flex; align-items: center; justify-content: center; color: #6c757d; background-color: #e9ecef; }
    .specialty-description { display: -webkit-box; min-height: 72px; overflow: hidden; -webkit-box-orient: vertical; -webkit-line-clamp: 3; }
</style>

<section class="specialty-banner">
    <div class="container">
        <h1 class="fw-bold">Đặc sản Cà Mau</h1>
        <p class="lead mb-0">Khám phá những sản vật mang đậm hương vị vùng đất cực Nam.</p>
    </div>
</section>

<main class="container py-5">
    <!-- Bộ lọc đa tiêu chí -->
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-4">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label for="q" class="form-label fw-semibold">Từ khóa tìm kiếm</label>
                    <input type="text" id="q" name="q" class="form-control" placeholder="Tên đặc sản, tôm, cua..." value="<?= htmlspecialchars($tuKhoa) ?>">
                </div>

                <div class="col-lg-3 col-md-6">
                    <label for="danh_muc_id" class="form-label fw-semibold">Danh mục</label>
                    <select id="danh_muc_id" name="danh_muc_id" class="form-select">
                        <option value="">Tất cả danh mục</option>
                        <?php foreach ($danhSachDanhMuc as $danhMuc): ?>
                            <option value="<?= (int) $danhMuc['id'] ?>" <?= $danhMucId === (int) $danhMuc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($danhMuc['ten_danh_muc']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label for="khu_vuc" class="form-label fw-semibold">Khu vực địa bàn</label>
                    <select id="khu_vuc" name="khu_vuc" class="form-select">
                        <option value="">Tất cả địa bàn</option>
                        <option value="Năm Căn" <?= $khuVuc === 'Năm Căn' ? 'selected' : '' ?>>Năm Căn</option>
                        <option value="U Minh" <?= $khuVuc === 'U Minh' ? 'selected' : '' ?>>U Minh</option>
                        <option value="Ngọc Hiển" <?= $khuVuc === 'Ngọc Hiển' ? 'selected' : '' ?>>Ngọc Hiển (Rạch Gốc, Đất Mũi)</option>
                        <option value="Cái Nước" <?= $khuVuc === 'Cái Nước' ? 'selected' : '' ?>>Cái Nước</option>
                        <option value="Trần Văn Thời" <?= $khuVuc === 'Trần Văn Thời' ? 'selected' : '' ?>>Trần Văn Thời</option>
                        <option value="Cà Mau" <?= $khuVuc === 'Cà Mau' ? 'selected' : '' ?>>TP. Cà Mau</option>
                    </select>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success flex-grow-1">Lọc đặc sản</button>
                        <a href="dac-san.php" class="btn btn-outline-secondary" title="Đặt lại bộ lọc">Xóa lọc</a>
                    </div>
                </div>

                <div class="col-12 mt-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="noi_bat" value="1" id="noi_bat" <?= $noiBat !== null ? 'checked' : '' ?>>
                        <label class="form-check-label text-muted" for="noi_bat">Chỉ hiển thị các đặc sản nổi bật</label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h2 class="section-title mb-1">Danh sách đặc sản</h2>
            <p class="text-muted mb-0">Tìm thấy <strong><?= $tongDacSan ?></strong> đặc sản phù hợp.</p>
        </div>
    </div>

    <?php if (empty($danhSachDacSan)): ?>
        <div class="alert alert-warning text-center py-4">Không tìm thấy đặc sản phù hợp với bộ lọc hiện tại.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($danhSachDacSan as $dacSan): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="card specialty-card h-100 shadow-sm">
                        <div class="position-relative">
                            <?php if (!empty($dacSan['hinh_anh'])): ?>
                                <img src="<?= htmlspecialchars($baseUrl) ?>/assets/uploads/dac-san/<?= htmlspecialchars($dacSan['hinh_anh']) ?>" alt="<?= htmlspecialchars($dacSan['ten_dac_san']) ?>">
                            <?php else: ?>
                                <div class="specialty-no-image">Chưa có hình ảnh</div>
                            <?php endif; ?>

                            <?php if ((int) $dacSan['noi_bat'] === 1): ?>
                                <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-3">Nổi bật</span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <span class="badge bg-success-subtle text-success">
                                    <?= htmlspecialchars($dacSan['ten_danh_muc'] ?? 'Chưa phân loại') ?>
                                </span>
                            </div>

                            <h3 class="h5 fw-bold"><?= htmlspecialchars($dacSan['ten_dac_san']) ?></h3>
                            <p class="text-muted specialty-description"><?= htmlspecialchars($dacSan['mo_ta_ngan'] ?: 'Đang cập nhật thông tin giới thiệu.') ?></p>

                            <a href="<?= htmlspecialchars($baseUrl) ?>/chi-tiet-dac-san.php?id=<?= (int) $dacSan['id'] ?>" class="btn btn-success mt-auto">Xem chi tiết</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($tongTrang > 1): ?>
            <nav class="mt-5" aria-label="Phân trang đặc sản">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars(taoDuongDanTrang(max(1, $page - 1), $tuKhoa, $danhMucId ?: null, $khuVuc, $noiBat)) ?>">Trước</a>
                    </li>

                    <?php for ($i = 1; $i <= $tongTrang; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars(taoDuongDanTrang($i, $tuKhoa, $danhMucId ?: null, $khuVuc, $noiBat)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $tongTrang ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars(taoDuongDanTrang(min($tongTrang, $page + 1), $tuKhoa, $danhMucId ?: null, $khuVuc, $noiBat)) ?>">Sau</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>