<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$slug = trim($_GET['slug'] ?? '');

if (!$id && $slug === '') {
    header('Location: dac-san.php');
    exit;
}

// 1. Lấy thông tin chi tiết đặc sản
if ($id) {
    $stmt = $pdo->prepare('
        SELECT ds.*, dm.ten_danh_muc, dm.slug AS danh_muc_slug
        FROM dac_san AS ds
        LEFT JOIN danh_muc AS dm ON dm.id = ds.danh_muc_id
        WHERE ds.id = :id AND ds.trang_thai = 1
        LIMIT 1
    ');
    $stmt->execute(['id' => $id]);
} else {
    $stmt = $pdo->prepare('
        SELECT ds.*, dm.ten_danh_muc, dm.slug AS danh_muc_slug
        FROM dac_san AS ds
        LEFT JOIN danh_muc AS dm ON dm.id = ds.danh_muc_id
        WHERE ds.slug = :slug AND ds.trang_thai = 1
        LIMIT 1
    ');
    $stmt->execute(['slug' => $slug]);
}

$dacSan = $stmt->fetch();

if (!$dacSan) {
    header('Location: dac-san.php');
    exit;
}

$dacSanId = (int)$dacSan['id'];
$pageTitle = htmlspecialchars($dacSan['ten_dac_san']) . ' - Đặc sản Cà Mau';
$currentPage = 'dac-san';

// 2. Thu thập toàn bộ gallery ảnh
$tatCaAnh = [];
if (!empty($dacSan['hinh_anh'])) {
    $tatCaAnh[] = [
        'url' => '/DuAnNgheCoBan_Nhom1/assets/uploads/dac-san/' . $dacSan['hinh_anh'],
        'alt' => $dacSan['ten_dac_san']
    ];
}

try {
    $stmtAnh = $pdo->prepare('SELECT * FROM hinh_anh_dac_san WHERE dac_san_id = :id ORDER BY id ASC');
    $stmtAnh->execute(['id' => $dacSanId]);
    $danhSachAnhPhu = $stmtAnh->fetchAll();

    foreach ($danhSachAnhPhu as $anh) {
        $tenFile = $anh['duong_dan'] ?? $anh['hinh_anh'] ?? $anh['duong_dan_anh'] ?? '';
        if ($tenFile !== '') {
            $tatCaAnh[] = [
                'url' => '/DuAnNgheCoBan_Nhom1/assets/uploads/dac-san/' . $tenFile,
                'alt' => $dacSan['ten_dac_san']
            ];
        }
    }
} catch (\PDOException $e) {
    // Bỏ qua nếu bảng chưa tạo
}

// 3. Lấy cơ sở sản xuất liên kết
$stmtCoSo = $pdo->prepare('
    SELECT cs.*
    FROM co_so_san_xuat AS cs
    INNER JOIN dac_san_co_so AS dscs ON dscs.co_so_id = cs.id
    WHERE dscs.dac_san_id = :id AND cs.trang_thai = 1
    ORDER BY cs.id DESC
');
$stmtCoSo->execute(['id' => $dacSanId]);
$danhSachCoSo = $stmtCoSo->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
    .gallery-main-container {
        position: relative;
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--cm-border);
    }
    .gallery-main-img {
        width: 100%;
        height: 420px;
        object-fit: cover;
        transition: opacity 0.25s ease-in-out;
    }
    .thumb-slider-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        margin-top: 12px;
        width: 100%;
    }
    .thumb-track {
        display: flex;
        gap: 8px;
        overflow-x: hidden;
        scroll-behavior: smooth;
        padding: 4px 2px;
        width: 100%;
    }
    .thumb-img {
        flex: 0 0 calc(20% - 7px);
        height: 70px;
        object-fit: cover;
        border-radius: 8px;
        cursor: pointer;
        border: 2px solid var(--cm-border);
        transition: all 0.2s ease;
    }
    .thumb-img:hover, .thumb-img.active {
        border-color: var(--cm-red);
        transform: translateY(-2px);
        box-shadow: 0 3px 8px rgba(100, 31, 37, 0.3);
    }
    .btn-thumb-nav {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid var(--cm-border);
        color: #333;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        z-index: 5;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        transition: all 0.2s ease;
    }
    .btn-thumb-nav:hover {
        background: var(--cm-red);
        color: #fff;
        border-color: var(--cm-red);
    }
</style>

<header class="specialty-banner">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-2 text-white-50">
                <li class="breadcrumb-item"><a href="index.php" class="text-white-50 text-decoration-none">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="dac-san.php" class="text-white-50 text-decoration-none">Đặc sản</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page"><?= htmlspecialchars($dacSan['ten_dac_san']) ?></li>
            </ol>
        </nav>
        <h1 class="fw-bold mb-0 text-white"><?= htmlspecialchars($dacSan['ten_dac_san']) ?></h1>
    </div>
</header>

<main class="container py-5">
    <div class="row g-5">
        <!-- Cột trái: Gallery ảnh -->
        <div class="col-lg-6">
            <div>
                <?php if (!empty($tatCaAnh)): ?>
                    <div class="gallery-main-container mb-3 shadow-sm">
                        <img 
                            id="currentMainImage" 
                            src="<?= htmlspecialchars($tatCaAnh[0]['url']) ?>" 
                            alt="<?= htmlspecialchars($tatCaAnh[0]['alt']) ?>" 
                            class="gallery-main-img"
                        >
                        <?php if ((int)($dacSan['noi_bat'] ?? 0) === 1): ?>
                            <span class="badge bg-danger position-absolute top-0 end-0 m-3 px-3 py-2 fs-6 shadow">
                                <i class="bi bi-star-fill me-1"></i> Đặc sản tiêu biểu
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (count($tatCaAnh) > 1): ?>
                        <div class="thumb-slider-wrapper">
                            <button type="button" class="btn-thumb-nav me-1" onclick="scrollThumbnails(-1)" title="Ảnh trước">
                                <i class="bi bi-chevron-left"></i>
                            </button>

                            <div class="thumb-track" id="thumbTrack">
                                <?php foreach ($tatCaAnh as $index => $itemAnh): ?>
                                    <img 
                                        src="<?= htmlspecialchars($itemAnh['url']) ?>" 
                                        alt="<?= htmlspecialchars($itemAnh['alt']) ?>" 
                                        class="thumb-img <?= $index === 0 ? 'active' : '' ?>"
                                        onclick="changeMainImage(this, '<?= htmlspecialchars($itemAnh['url']) ?>')"
                                    >
                                <?php endforeach; ?>
                            </div>

                            <button type="button" class="btn-thumb-nav ms-1" onclick="scrollThumbnails(1)" title="Ảnh tiếp theo">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="gallery-main-img d-flex align-items-center justify-content-center bg-light text-muted rounded-3">
                        <i class="bi bi-image fs-1 me-2"></i> Chưa có hình ảnh
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cột phải: Chi tiết & Thông số thực tế từ DB -->
        <div class="col-lg-6">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge bg-secondary px-3 py-2">
                    <i class="bi bi-tag-fill me-1"></i> <?= htmlspecialchars($dacSan['ten_danh_muc'] ?? 'Đặc sản Cà Mau') ?>
                </span>
                <span class="badge badge-ocop px-3 py-2">
                    <i class="bi bi-award-fill me-1"></i> Chứng nhận OCOP Cà Mau
                </span>
                <span class="badge badge-geo px-3 py-2">
                    <i class="bi bi-geo-alt-fill me-1"></i> Chỉ dẫn địa lý Đất Mũi
                </span>
            </div>

            <h2 class="h3 fw-bold mb-3"><?= htmlspecialchars($dacSan['ten_dac_san']) ?></h2>

            <p class="lead text-secondary mb-4 fs-6">
                <?= nl2br(htmlspecialchars($dacSan['mo_ta_ngan'] ?: 'Sản vật trứ danh kết tinh từ hệ sinh thái đặc trưng của vùng đất Cà Mau.')) ?>
            </p>

            <div class="card p-3 mb-4 border-0 shadow-sm rounded-3" style="background-color: var(--cm-bg-soft); border-left: 4px solid var(--cm-red) !important;">
                <h5 class="fw-bold mb-3"><i class="bi bi-info-circle-fill text-danger me-2"></i>Thông tin đặc trưng</h5>
                <div class="row g-2 small">
                    <div class="col-12 mb-1">
                        <strong>Khu vực / Nguồn gốc:</strong> <?= htmlspecialchars($dacSan['nguon_goc'] ?: 'Năm Căn, U Minh, Ngọc Hiển, Cà Mau') ?>
                    </div>
                    <div class="col-12 mb-1">
                        <strong>Cách sử dụng:</strong> <?= htmlspecialchars($dacSan['cach_su_dung'] ?: 'Dùng trực tiếp, chế biến món ngon hoặc làm quà biếu.') ?>
                    </div>
                    <div class="col-12">
                        <strong>Bảo quản:</strong> <?= htmlspecialchars($dacSan['cach_bao_quan'] ?: 'Nơi khô ráo, thoáng mát, tránh ánh nắng trực tiếp.') ?>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-3 mb-4">
                <a href="#danh-sach-co-so" class="btn btn-success px-4 py-2 fw-semibold">
                    <i class="bi bi-shop me-1"></i> Xem nơi bán & Cơ sở (<?= count($danhSachCoSo) ?>)
                </a>
                <a href="dac-san.php" class="btn btn-outline-secondary px-4 py-2">
                    <i class="bi bi-arrow-left me-1"></i> Danh sách đặc sản
                </a>
            </div>
        </div>
    </div>

    <!-- Nội dung chi tiết nếu có -->
    <?php 
        $noiDungDayDu = $dacSan['mo_ta_chi_tiet'] ?? $dacSan['noi_dung_chi_tiet'] ?? $dacSan['noi_dung'] ?? '';
    ?>
    <?php if (!empty($noiDungDayDu)): ?>
        <section class="mt-5 p-4 rounded-4 bg-light border">
            <h4 class="fw-bold mb-3 text-danger"><i class="bi bi-journal-text me-2"></i>Giới thiệu chi tiết sản vật</h4>
            <div class="text-secondary" style="line-height: 1.8; white-space: pre-line;">
                <?= htmlspecialchars($noiDungDayDu) ?>
            </div>
        </section>
    <?php endif; ?>

    <hr class="my-5">

    <!-- Danh sách cơ sở liên kết -->
    <section id="danh-sach-co-so" class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Cơ sở sản xuất & Điểm bán uy tín</h3>
                <p class="text-muted mb-0">Địa chỉ mua trực tiếp sản phẩm chính gốc tại Cà Mau</p>
            </div>
        </div>

        <?php if (empty($danhSachCoSo)): ?>
            <div class="alert alert-info">Hiện chưa có thông tin cơ sở sản xuất liên kết cho sản phẩm này.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($danhSachCoSo as $coSo): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm p-4 rounded-4">
                            <h5 class="fw-bold text-danger mb-2"><?= htmlspecialchars($coSo['ten_co_so']) ?></h5>
                            <p class="small text-muted mb-2">
                                <i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($coSo['dia_chi']) ?>
                            </p>
                            <?php if (!empty($coSo['so_dien_thoai'])): ?>
                                <p class="small text-muted mb-3">
                                    <i class="bi bi-telephone-fill text-success me-1"></i> <?= htmlspecialchars($coSo['so_dien_thoai']) ?>
                                </p>
                            <?php endif; ?>
                            <a href="<?= htmlspecialchars(taoGoogleMapsUrl($coSo)) ?>" target="_blank" class="btn btn-outline-danger btn-sm mt-auto">
                                <i class="bi bi-map me-1"></i> Chỉ đường Google Maps
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<script>
let currentImageIndex = 0;

function changeMainImage(thumbElement, imageSrc) {
    const mainImg = document.getElementById('currentMainImage');
    if (!mainImg) return;

    mainImg.style.opacity = '0.3';
    setTimeout(() => {
        mainImg.src = imageSrc;
        mainImg.style.opacity = '1';
    }, 150);

    const allThumbs = document.querySelectorAll('.thumb-img');
    allThumbs.forEach((el, idx) => {
        el.classList.remove('active');
        if (el === thumbElement) {
            currentImageIndex = idx;
        }
    });

    thumbElement.classList.add('active');
}

function scrollThumbnails(direction) {
    const track = document.getElementById('thumbTrack');
    const allThumbs = document.querySelectorAll('.thumb-img');
    if (!track || allThumbs.length === 0) return;

    let nextIndex = currentImageIndex + direction;

    if (nextIndex < 0) nextIndex = 0;
    else if (nextIndex >= allThumbs.length) nextIndex = allThumbs.length - 1;

    if (nextIndex !== currentImageIndex) {
        currentImageIndex = nextIndex;
        const targetThumb = allThumbs[currentImageIndex];

        const mainImg = document.getElementById('currentMainImage');
        if (mainImg) {
            mainImg.style.opacity = '0.3';
            setTimeout(() => {
                mainImg.src = targetThumb.src;
                mainImg.style.opacity = '1';
            }, 150);
        }

        allThumbs.forEach(el => el.classList.remove('active'));
        targetThumb.classList.add('active');

        targetThumb.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
            inline: 'center'
        });
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>