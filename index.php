<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$pageTitle = 'Đặc sản Cà Mau - Tinh hoa vùng đất cực Nam';
$currentPage = 'trang-chu';

// 1. Lấy 6 đặc sản nổi bật
$stmtDacSan = $pdo->query(
    'SELECT
        ds.id,
        ds.ten_dac_san,
        ds.mo_ta_ngan,
        ds.hinh_anh,
        ds.noi_bat,
        dm.ten_danh_muc
     FROM dac_san AS ds
     LEFT JOIN danh_muc AS dm ON dm.id = ds.danh_muc_id
     WHERE ds.trang_thai = 1
     ORDER BY ds.noi_bat DESC, ds.id DESC
     LIMIT 6'
);
$danhSachDacSan = $stmtDacSan->fetchAll();

// 2. Lấy 3 cơ sở sản xuất tiêu biểu
$stmtCoSo = $pdo->query(
    'SELECT
        cs.id,
        cs.ten_co_so,
        cs.dia_chi,
        cs.mo_ta,
        cs.hinh_anh,
        cs.vi_do,
        cs.kinh_do,
        cs.google_maps_url,
        GROUP_CONCAT(DISTINCT ds.ten_dac_san ORDER BY ds.ten_dac_san SEPARATOR ", ") AS danh_sach_dac_san
     FROM co_so_san_xuat AS cs
     LEFT JOIN dac_san_co_so AS dscs ON dscs.co_so_id = cs.id
     LEFT JOIN dac_san AS ds ON ds.id = dscs.dac_san_id AND ds.trang_thai = 1
     WHERE cs.trang_thai = 1
     GROUP BY cs.id, cs.ten_co_so, cs.dia_chi, cs.mo_ta, cs.hinh_anh, cs.vi_do, cs.kinh_do, cs.google_maps_url
     ORDER BY cs.id DESC
     LIMIT 3'
);
$danhSachCoSo = $stmtCoSo->fetchAll();

// 3. Lấy các bài viết đã xuất bản cho Slider trang chủ
$stmtBaiViet = $pdo->query(
    "SELECT id, tieu_de, tom_tat, hinh_anh, ngay_dang
     FROM bai_viet
     WHERE trang_thai = 'xuat_ban'
     ORDER BY ngay_dang DESC, id DESC
     LIMIT 6"
);
$danhSachBaiViet = $stmtBaiViet->fetchAll();

function taoGoogleMapsUrlTrangChu(array $coSo): string
{
    if (!empty($coSo['google_maps_url'])) {
        return $coSo['google_maps_url'];
    }
    if ($coSo['vi_do'] !== null && $coSo['kinh_do'] !== null) {
        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($coSo['vi_do'] . ',' . $coSo['kinh_do']);
    }
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($coSo['ten_co_so'] . ', ' . $coSo['dia_chi']);
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- 1. Hero Banner chính -->
<section class="home-hero">
    <div class="container py-5">
        <div style="max-width: 760px;">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(6px); border: 1px solid rgba(255,255,255,0.25); color: #fff; padding: 7px 14px; font-size: 12px; letter-spacing: 1px; text-transform: uppercase;">
                    <i class="bi bi-geo-alt-fill text-warning me-1"></i> Tinh hoa vùng đất cực Nam
                </span>
            </div>
            <h1 class="display-4 fw-bold">
                Khám phá đặc sản & văn hóa ẩm thực Cà Mau
            </h1>
            <p class="lead mb-4">
                Tìm hiểu nguồn gốc, cách sử dụng, cơ sở sản xuất và những câu chuyện di sản gắn liền với các sản vật nức tiếng của vùng đất Đất Mũi.
            </p>
            <div class="d-flex flex-wrap gap-3">
                <a href="<?= $baseUrl ?>/dac-san.php" class="btn-cm-primary">
                    <i class="bi bi-compass"></i> Khám phá đặc sản
                </a>
                <a href="<?= $baseUrl ?>/ban-do.php" class="btn-cm-outline">
                    <i class="bi bi-map"></i> Xem bản đồ phân bố
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 2. Giới thiệu tổng quan -->
<section class="home-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <div class="section-subtitle">Về đặc sản Cà Mau</div>
                <h2 class="home-title display-6">Hương vị độc bản từ rừng, biển phương Nam</h2>
            </div>
            <div class="col-lg-7">
                <p class="lead mb-0 text-muted" style="font-size: 16.5px; line-height: 1.8;">
                    Cà Mau nổi tiếng với cua biển Năm Căn, tôm khô Đất Mũi, ba khía Rạch Gốc, khô cá bổi, mật ong rừng U Minh Hạ. Mỗi đặc sản đều gắn liền với môi trường tự nhiên, nghề truyền thống và đời sống văn hóa của người dân địa phương qua nhiều thế hệ.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- 3. Đặc sản tiêu biểu -->
<section class="home-section home-section-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-5">
            <div>
                <div class="section-subtitle">Sản vật địa phương</div>
                <h2 class="home-title mb-0">Đặc sản nổi bật</h2>
            </div>
            <a href="<?= $baseUrl ?>/dac-san.php" class="btn btn-outline-dark btn-sm px-3 py-2 fw-semibold rounded-3">
                Xem tất cả đặc sản <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <?php if (empty($danhSachDacSan)): ?>
            <div class="alert alert-info rounded-4 py-4 text-center">Danh sách đặc sản đang được cập nhật.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($danhSachDacSan as $dacSan): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="specialty-card">
                            <div class="card-img-wrapper">
                                <?php if (!empty($dacSan['hinh_anh'])): ?>
                                    <img
                                        src="<?= $baseUrl ?>/assets/uploads/dac-san/<?= htmlspecialchars($dacSan['hinh_anh']) ?>"
                                        alt="<?= htmlspecialchars($dacSan['ten_dac_san']) ?>"
                                        class="specialty-image"
                                    >
                                <?php else: ?>
                                    <div class="specialty-no-image"><i class="bi bi-image fs-1"></i></div>
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
                                        <?= htmlspecialchars($dacSan['ten_danh_muc'] ?? 'Đặc sản Đất Mũi') ?>
                                    </span>
                                </div>

                                <h3 class="h5 fw-bold mb-2 text-dark"><?= htmlspecialchars($dacSan['ten_dac_san']) ?></h3>

                                <p class="limited-text">
                                    <?= htmlspecialchars($dacSan['mo_ta_ngan'] ?: 'Đặc sản trứ danh mang phong vị tự nhiên của vùng đất rừng ngập mặn Cà Mau.') ?>
                                </p>

                                <a href="<?= $baseUrl ?>/chi-tiet-dac-san.php?id=<?= (int)$dacSan['id'] ?>" class="btn-cm-card mt-auto">
                                    <span>Xem chi tiết</span> <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- 4. Khối Di sản & Câu chuyện văn hóa (Slider) -->
<section class="home-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4" style="max-width: 1100px; margin: 0 auto;">
            <div>
                <div class="section-subtitle">Văn hóa & Con người Đất Mũi</div>
                <h2 class="home-title mb-0">Câu chuyện & Di sản nổi bật</h2>
            </div>
            <a href="<?= $baseUrl ?>/bai-viet.php" class="btn btn-outline-dark btn-sm px-3 py-2 fw-semibold rounded-3">
                Xem tất cả bài viết <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <?php if (empty($danhSachBaiViet)): ?>
            <div class="alert alert-info text-center" style="max-width: 1100px; margin: 0 auto;">
                Chưa có câu chuyện nào được xuất bản.
            </div>
        <?php else: ?>
            <div class="story-slider-container">
                <!-- Nút chuyển sang trái -->
                <button type="button" class="story-btn-nav story-btn-prev" onclick="chuyenSlideBaiViet(-1)" title="Bài viết trước">
                    <i class="bi bi-chevron-left fs-5"></i>
                </button>

                <!-- Danh sách các bài viết dạng Slide -->
                <?php foreach ($danhSachBaiViet as $index => $bv): ?>
                    <div class="story-slide-item <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
                        <div class="story-box-premium">
                            <div class="row align-items-center g-4 p-2 p-md-3">
                                <div class="col-lg-6">
                                    <?php if (!empty($bv['hinh_anh'])): ?>
                                        <img 
                                            src="<?= $baseUrl ?>/assets/uploads/bai-viet/<?= htmlspecialchars($bv['hinh_anh']) ?>" 
                                            alt="<?= htmlspecialchars($bv['tieu_de']) ?>" 
                                            class="story-box-img"
                                        >
                                    <?php else: ?>
                                        <img 
                                            src="<?= $baseUrl ?>/assets/images/banner-ca-mau.jpg" 
                                            alt="<?= htmlspecialchars($bv['tieu_de']) ?>" 
                                            class="story-box-img"
                                        >
                                    <?php endif; ?>
                                </div>
                                <div class="col-lg-6 p-4 p-lg-5">
                                    <span class="badge-category mb-3">
                                        <i class="bi bi-award-fill me-1"></i> Di sản văn hóa & Ẩm thực
                                    </span>
                                    <h2 class="home-title mb-3" style="font-size: 28px;">
                                        <?= htmlspecialchars($bv['tieu_de']) ?>
                                    </h2>
                                    <p class="text-muted mb-4" style="line-height: 1.8;">
                                        <?= htmlspecialchars($bv['tom_tat'] ?: 'Khám phá nét đẹp văn hóa và những câu chuyện truyền thống đặc sắc của vùng đất cực Nam.') ?>
                                    </p>
                                    <a href="<?= $baseUrl ?>/chi-tiet-bai-viet.php?id=<?= (int)$bv['id'] ?>" class="btn-cm-primary">
                                        <span>Đọc câu chuyện chi tiết</span> <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Nút chuyển sang phải -->
                <button type="button" class="story-btn-nav story-btn-next" onclick="chuyenSlideBaiViet(1)" title="Bài viết kế tiếp">
                    <i class="bi bi-chevron-right fs-5"></i>
                </button>

                <!-- Các chấm tròn điều hướng -->
                <div class="story-dots">
                    <?php for ($i = 0; $i < count($danhSachBaiViet); $i++): ?>
                        <div class="story-dot <?= $i === 0 ? 'active' : '' ?>" onclick="denSlideBaiViet(<?= $i ?>)"></div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- 5. Cơ sở sản xuất tiêu biểu -->
<section class="home-section home-section-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-5">
            <div>
                <div class="section-subtitle">Địa điểm tiêu biểu</div>
                <h2 class="home-title mb-0">Cơ sở sản xuất & Điểm bán</h2>
            </div>
            <a href="<?= $baseUrl ?>/co-so-san-xuat.php" class="btn btn-outline-dark btn-sm px-3 py-2 fw-semibold rounded-3">
                Xem tất cả cơ sở <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <?php if (!empty($danhSachCoSo)): ?>
            <div class="row g-4">
                <?php foreach ($danhSachCoSo as $coSo): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="facility-card">
                            <div class="card-img-wrapper" style="height: 190px; background: #fff; display: flex; align-items: center; justify-content: center;">
                                <?php if (!empty($coSo['hinh_anh'])): ?>
                                    <img src="<?= $baseUrl ?>/assets/uploads/co-so/<?= htmlspecialchars($coSo['hinh_anh']) ?>" alt="<?= htmlspecialchars($coSo['ten_co_so']) ?>" class="facility-image" style="object-fit: contain; padding: 12px;">
                                <?php else: ?>
                                    <div class="facility-no-image"><i class="bi bi-shop fs-1"></i></div>
                                <?php endif; ?>
                            </div>

                            <div class="card-body-custom">
                                <h3 class="h6 fw-bold mb-2 text-dark" style="font-size: 16px;"><?= htmlspecialchars($coSo['ten_co_so']) ?></h3>
                                <p class="small text-muted mb-2">
                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($coSo['dia_chi']) ?>
                                </p>
                                <?php if (!empty($coSo['danh_sach_dac_san'])): ?>
                                    <p class="small text-muted limited-text mb-3">
                                        <strong>Đặc sản:</strong> <?= htmlspecialchars($coSo['danh_sach_dac_san']) ?>
                                    </p>
                                <?php endif; ?>
                                <a href="<?= htmlspecialchars(taoGoogleMapsUrlTrangChu($coSo)) ?>" target="_blank" rel="noopener noreferrer" class="btn-cm-card mt-auto">
                                    <i class="bi bi-geo-alt"></i> Xem trên Google Maps
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Script điều khiển chuyển slide bài viết -->
<script>
let currentStorySlide = 0;
const storySlides = document.querySelectorAll('.story-slide-item');
const storyDots = document.querySelectorAll('.story-dot');

function hienThiSlideBaiViet(index) {
    if (storySlides.length === 0) return;

    if (index >= storySlides.length) currentStorySlide = 0;
    else if (index < 0) currentStorySlide = storySlides.length - 1;
    else currentStorySlide = index;

    storySlides.forEach((slide, i) => {
        slide.classList.toggle('active', i === currentStorySlide);
    });

    storyDots.forEach((dot, i) => {
        dot.classList.toggle('active', i === currentStorySlide);
    });
}

function chuyenSlideBaiViet(huong) {
    hienThiSlideBaiViet(currentStorySlide + huong);
}

function denSlideBaiViet(index) {
    hienThiSlideBaiViet(index);
}

// Tự động trượt slide sau 6 giây
let autoSlideInterval = setInterval(() => chuyenSlideBaiViet(1), 6000);

const sliderBox = document.querySelector('.story-slider-container');
if (sliderBox) {
    sliderBox.addEventListener('mouseenter', () => clearInterval(autoSlideInterval));
    sliderBox.addEventListener('mouseleave', () => {
        autoSlideInterval = setInterval(() => chuyenSlideBaiViet(1), 6000);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>