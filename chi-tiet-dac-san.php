<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$currentPage = 'dac-san';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {    
    header('Location: /DuAnNgheCoBan_Nhom1/dac-san.php');    
    exit;    
}

/* Lấy thông tin chi tiết đặc sản */    
$stmt = $pdo->prepare(    
    'SELECT    
        ds.id,    
        ds.danh_muc_id,    
        ds.ten_dac_san,    
        ds.slug,    
        ds.mo_ta_ngan,    
        ds.nguon_goc,    
        ds.mo_ta_chi_tiet,    
        ds.cach_su_dung,    
        ds.cach_bao_quan,    
        ds.hinh_anh,    
        ds.noi_bat,    
        dm.ten_danh_muc    
     FROM dac_san AS ds    
     LEFT JOIN danh_muc AS dm    
        ON dm.id = ds.danh_muc_id    
     WHERE ds.id = :id    
       AND ds.trang_thai = 1    
     LIMIT 1'    
);

$stmt->execute(['id' => $id]);
$dacSan = $stmt->fetch();

if (!$dacSan) {    
    http_response_code(404);
    $pageTitle = 'Không tìm thấy đặc sản';

    require_once __DIR__ . '/includes/header.php';    
    require_once __DIR__ . '/includes/navbar.php';    
    ?>

    <main class="container py-5">    
        <div class="alert alert-warning text-center py-5">    
            <h1 class="h3">Không tìm thấy đặc sản</h1>    
            <p>Đặc sản không tồn tại hoặc đang tạm ẩn khỏi website.</p>    
            <a href="/DuAnNgheCoBan_Nhom1/dac-san.php" class="btn btn-success">    
                Quay lại danh sách    
            </a>    
        </div>    
    </main>

    <?php    
    require_once __DIR__ . '/includes/footer.php';    
    exit;    
}

$pageTitle = $dacSan['ten_dac_san'] . ' - Đặc sản Cà Mau';

/* Lấy thư viện ảnh phụ (Gallery) */
$stmtGallery = $pdo->prepare('SELECT duong_dan, mo_ta FROM hinh_anh_dac_san WHERE dac_san_id = :id ORDER BY thu_tu ASC');
$stmtGallery->execute(['id' => $id]);
$galleryAnh = $stmtGallery->fetchAll();

/* Lấy các cơ sở có đặc sản này */    
$stmtCoSo = $pdo->prepare(    
    'SELECT    
        cs.id,    
        cs.ten_co_so,    
        cs.dia_chi,    
        cs.so_dien_thoai,    
        cs.email,    
        cs.mo_ta,    
        cs.hinh_anh,    
        cs.vi_do,    
        cs.kinh_do,    
        cs.google_maps_url,    
        dscs.ghi_chu    
     FROM dac_san_co_so AS dscs    
     INNER JOIN co_so_san_xuat AS cs    
        ON cs.id = dscs.co_so_id    
     WHERE dscs.dac_san_id = :dac_san_id    
       AND cs.trang_thai = 1    
     ORDER BY cs.ten_co_so ASC'    
);

$stmtCoSo->execute(['dac_san_id' => $id]);
$danhSachCoSo = $stmtCoSo->fetchAll();

/* Lấy tối đa 3 đặc sản liên quan cùng danh mục */    
if (!empty($dacSan['danh_muc_id'])) {    
    $stmtLienQuan = $pdo->prepare(    
        'SELECT id, ten_dac_san, mo_ta_ngan, hinh_anh    
         FROM dac_san    
         WHERE trang_thai = 1 AND danh_muc_id = :danh_muc_id AND id <> :id    
         ORDER BY noi_bat DESC, id DESC LIMIT 3'    
    );
    $stmtLienQuan->execute(['danh_muc_id' => $dacSan['danh_muc_id'], 'id' => $id]);    
} else {    
    $stmtLienQuan = $pdo->prepare(    
        'SELECT id, ten_dac_san, mo_ta_ngan, hinh_anh    
         FROM dac_san    
         WHERE trang_thai = 1 AND id <> :id    
         ORDER BY noi_bat DESC, id DESC LIMIT 3'    
    );
    $stmtLienQuan->execute(['id' => $id]);    
}

$danhSachLienQuan = $stmtLienQuan->fetchAll();

require_once __DIR__ . '/includes/header.php';    
require_once __DIR__ . '/includes/navbar.php';    
?>

<!-- Leaflet Maps CSS/JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />  
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>    
    .detail-banner { padding: 28px 0; background-color: #f4f8f5; border-bottom: 1px solid #e2e8e4; }
    .detail-image { width: 100%; height: 420px; object-fit: cover; border-radius: 14px; }
    .detail-no-image { width: 100%; height: 420px; display: flex; align-items: center; justify-content: center; border-radius: 14px; color: #6c757d; background-color: #e9ecef; }
    .information-box { height: 100%; padding: 24px; border-radius: 12px; background-color: #f8faf8; border-left: 5px solid #198754; }
    .related-card { overflow: hidden; border: 0; border-radius: 12px; }
    .related-image, .related-no-image { width: 100%; height: 190px; object-fit: cover; }
    .related-no-image { display: flex; align-items: center; justify-content: center; color: #6c757d; background-color: #e9ecef; }
    .content-text { line-height: 1.8; white-space: normal; }

    #detail-map { height: 480px; width: 100%; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
    .facility-scroll-container { max-height: 480px; overflow-y: auto; padding: 6px 8px 6px 6px; }
    .facility-select-card { cursor: pointer; border: 2px solid #e9ecef; border-radius: 12px; transition: all 0.2s ease-in-out; }
    .facility-select-card:hover { border-color: #198754; background-color: #f4f8f5; transform: translateY(-2px); }
    .facility-select-card.active-facility { border-color: #198754; background-color: #e8f5e9; box-shadow: 0 4px 10px rgba(25, 135, 84, 0.15); position: relative; z-index: 2; }
    .gallery-thumb { width: 75px; height: 55px; object-fit: cover; border-radius: 6px; cursor: pointer; border: 2px solid transparent; transition: all 0.2s; }
    .gallery-thumb:hover { opacity: 0.85; }

    .map-fullscreen { position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; z-index: 9999 !important; border-radius: 0 !important; }
    @media (max-width: 768px) { .detail-image, .detail-no-image { height: 300px; } #detail-map { height: 350px; } .facility-scroll-container { max-height: none; } }
</style>

<section class="detail-banner">    
    <div class="container">    
        <nav aria-label="breadcrumb">    
            <ol class="breadcrumb mb-2">    
                <li class="breadcrumb-item"><a href="/DuAnNgheCoBan_Nhom1/">Trang chủ</a></li>    
                <li class="breadcrumb-item"><a href="/DuAnNgheCoBan_Nhom1/dac-san.php">Đặc sản</a></li>    
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($dacSan['ten_dac_san']) ?></li>    
            </ol>    
        </nav>  
        <h1 class="fw-bold text-success mb-0"><?= htmlspecialchars($dacSan['ten_dac_san']) ?></h1>    
    </div>    
</section>

<main class="container py-5">    
    <section class="row g-5 align-items-start">    
        <div class="col-lg-6">    
            <?php if (!empty($dacSan['hinh_anh'])): ?>    
                <img id="main-product-image" src="/DuAnNgheCoBan_Nhom1/assets/uploads/dac-san/<?= htmlspecialchars($dacSan['hinh_anh']) ?>" alt="<?= htmlspecialchars($dacSan['ten_dac_san']) ?>" class="detail-image shadow-sm mb-3">    
            <?php else: ?>    
                <div class="detail-no-image mb-3">Chưa có hình ảnh</div>    
            <?php endif; ?>

            <!-- Thư viện ảnh nhỏ (Gallery) -->
            <?php if (!empty($galleryAnh)): ?>
                <div class="d-flex gap-2 overflow-auto pb-2">
                    <?php if (!empty($dacSan['hinh_anh'])): ?>
                        <img src="/DuAnNgheCoBan_Nhom1/assets/uploads/dac-san/<?= htmlspecialchars($dacSan['hinh_anh']) ?>" class="gallery-thumb" onclick="changeMainImage(this)" style="border-color: #198754;">
                    <?php endif; ?>
                    <?php foreach ($galleryAnh as $img): ?>
                        <img src="/DuAnNgheCoBan_Nhom1/assets/uploads/dac-san/<?= htmlspecialchars($img['duong_dan']) ?>" class="gallery-thumb" onclick="changeMainImage(this)">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-6">    
            <div class="mb-3">    
                <span class="badge bg-success fs-6"><?= htmlspecialchars($dacSan['ten_danh_muc'] ?? 'Chưa phân loại') ?></span>  
                <?php if ((int) $dacSan['noi_bat'] === 1): ?>    
                    <span class="badge bg-warning text-dark fs-6">Đặc sản nổi bật</span>    
                <?php endif; ?>    
            </div>
            <h2 class="display-6 fw-bold"><?= htmlspecialchars($dacSan['ten_dac_san']) ?></h2>  
            <p class="lead text-muted"><?= htmlspecialchars($dacSan['mo_ta_ngan'] ?: 'Thông tin giới thiệu đang được cập nhật.') ?></p>  

            <?php if (!empty($dacSan['nguon_goc'])): ?>    
                <div class="information-box mt-4">    
                    <h3 class="h5 text-success">Nguồn gốc</h3>    
                    <div class="content-text"><?= nl2br(htmlspecialchars($dacSan['nguon_goc'])) ?></div>    
                </div>    
            <?php endif; ?>  

            <div class="d-flex flex-wrap gap-2 mt-4">    
                <a href="#co-so-san-xuat" class="btn btn-success">Xem địa điểm</a>    
                <a href="/DuAnNgheCoBan_Nhom1/dac-san.php" class="btn btn-outline-secondary">Xem đặc sản khác</a>    
            </div>    
        </div>    
    </section>

    <!-- MỤC CƠ SỞ SẢN XUẤT & BẢN ĐỒ TƯƠNG TÁC -->  
    <section id="co-so-san-xuat" class="py-5 border-bottom">    
        <div class="mb-4">    
            <h2 class="section-title mb-1">Địa điểm & Cơ sở sản xuất</h2>    
            <p class="text-muted mb-0">Chọn một cơ sở trong danh sách bên dưới để xem vị trí chính xác trên bản đồ.</p>    
        </div>  

        <?php if (empty($danhSachCoSo)): ?>    
            <div class="alert alert-info">Địa điểm của đặc sản này đang được cập nhật.</div>    
        <?php else: ?>    
            <div class="row g-4 align-items-start">  
                <!-- Cột Danh Sách Cơ Sở -->  
                <div class="col-lg-5">  
                    <div class="facility-scroll-container">  
                        <?php foreach ($danhSachCoSo as $index => $coSo): ?>  
                            <?php 
                                $viDo = filter_var($coSo['vi_do'], FILTER_VALIDATE_FLOAT);
                                $kinhDo = filter_var($coSo['kinh_do'], FILTER_VALIDATE_FLOAT);
                                $coViTri = ($viDo !== false && $kinhDo !== false && $viDo >= -90 && $viDo <= 90 && $kinhDo >= -180 && $kinhDo <= 180); 
                            ?>  
                            <div class="card facility-select-card mb-3 p-3" id="facility-card-<?= (int)$coSo['id'] ?>" <?php if ($coViTri): ?> onclick="chonCoSoOnMap(<?= (int)$coSo['id'] ?>, <?= (float)$viDo ?>, <?= (float)$kinhDo ?>)" <?php endif; ?>>  
                                <div class="d-flex justify-content-between align-items-start mb-2">  
                                    <h3 class="h6 fw-bold text-success mb-0"><?= htmlspecialchars($coSo['ten_co_so']) ?></h3>  
                                    <span class="badge <?= $coViTri ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border' ?>">
                                        <?= $coViTri ? 'Có vị trí' : 'Chưa có vị trí' ?>
                                    </span>
                                </div>
                                <p class="small text-muted mb-1"><strong>Địa chỉ:</strong> <?= htmlspecialchars($coSo['dia_chi']) ?></p>
                                <?php if (!empty($coSo['so_dien_thoai'])): ?>  
                                    <p class="small text-muted mb-2"><strong>Điện thoại:</strong> <a href="tel:<?= htmlspecialchars($coSo['so_dien_thoai']) ?>"><?= htmlspecialchars($coSo['so_dien_thoai']) ?></a></p>  
                                <?php endif; ?>  

                                <div class="d-flex gap-2 mt-2 pt-1 border-top">  
                                    <?php if ($coViTri): ?>  
                                        <button type="button" class="btn btn-sm btn-success flex-grow-1" onclick="chonCoSoOnMap(<?= (int)$coSo['id'] ?>, <?= (float)$viDo ?>, <?= (float)$kinhDo ?>)">Xem bản đồ</button>  
                                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $viDo ?>,<?= $kinhDo ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation();">
                                            🧭 Chỉ đường
                                        </a>
                                    <?php endif; ?>  
                                    <a href="<?= htmlspecialchars(taoGoogleMapsUrl($coSo)) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();">Google Maps</a>  
                                </div>  
                            </div>  
                        <?php endforeach; ?>  
                    </div>  
                </div>

                <!-- Cột Bản Đồ -->  
                <div class="col-lg-7">  
                    <div class="sticky-top" style="top: 90px;">  
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-success"><i class="bi bi-geo-alt-fill"></i> Bản đồ vị trí</span>
                            <button type="button" id="btn-toggle-fullscreen" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-arrows-fullscreen"></i> Phóng to bản đồ
                            </button>
                        </div>
                        <div id="detail-map"></div>  
                    </div>  
                </div>  
            </div>  
        <?php endif; ?>    
    </section>  

    <?php if (!empty($danhSachLienQuan)): ?>    
        <section class="pt-5">    
            <h2 class="section-title mb-4">Đặc sản liên quan</h2>    
            <div class="row g-4">    
                <?php foreach ($danhSachLienQuan as $lienQuan): ?>    
                    <div class="col-md-6 col-lg-4">    
                        <div class="card related-card h-100 shadow-sm">    
                            <?php if (!empty($lienQuan['hinh_anh'])): ?>    
                                <img src="/DuAnNgheCoBan_Nhom1/assets/uploads/dac-san/<?= htmlspecialchars($lienQuan['hinh_anh']) ?>" alt="<?= htmlspecialchars($lienQuan['ten_dac_san']) ?>" class="related-image">    
                            <?php else: ?>    
                                <div class="related-no-image">Chưa có hình ảnh</div>    
                            <?php endif; ?>    
                            <div class="card-body d-flex flex-column">    
                                <h3 class="h5 fw-bold"><?= htmlspecialchars($lienQuan['ten_dac_san']) ?></h3>    
                                <p class="text-muted"><?= htmlspecialchars($lienQuan['mo_ta_ngan'] ?: 'Thông tin đang được cập nhật.') ?></p>    
                                <a href="/DuAnNgheCoBan_Nhom1/chi-tiet-dac-san.php?id=<?= (int) $lienQuan['id'] ?>" class="btn btn-outline-success mt-auto">Xem chi tiết</a>    
                            </div>    
                        </div>    
                    </div>    
                <?php endforeach; ?>    
            </div>    
        </section>    
    <?php endif; ?>    
</main>

<script>  
var map;  
var markersMap = {};

function changeMainImage(el) {
    const mainImg = document.getElementById('main-product-image');
    if (mainImg) mainImg.src = el.src;
    document.querySelectorAll('.gallery-thumb').forEach(thumb => thumb.style.borderColor = 'transparent');
    el.style.borderColor = '#198754';
}

function chonCoSoOnMap(id, lat, lng) {  
    document.querySelectorAll('.facility-select-card').forEach(function(card) {  
        card.classList.remove('active-facility');  
    });

    var selectedCard = document.getElementById('facility-card-' + id);  
    if (selectedCard) {  
        selectedCard.classList.add('active-facility');  
    }

    if (map && markersMap[id]) {  
        map.setView([lat, lng], 15, { animate: true });  
        markersMap[id].openPopup();  
    }  
}

document.addEventListener("DOMContentLoaded", function() {  
    var coSoList = <?= json_encode($danhSachCoSo, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;  
    
    var validLocations = coSoList.filter(function(item) {
        var lat = parseFloat(item.vi_do);
        var lng = parseFloat(item.kinh_do);
        return !isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180 && (lat !== 0 || lng !== 0);
    });

    var mapElement = document.getElementById('detail-map');  
    if (!mapElement) return;

    if (validLocations.length === 0) {  
        mapElement.innerHTML = '<div class="p-4 text-center text-muted">Chưa có thông tin tọa độ bản đồ hợp lệ cho các cơ sở này.</div>';  
        return;  
    }

    var firstLat = parseFloat(validLocations[0].vi_do);
    var firstLng = parseFloat(validLocations[0].kinh_do);

    map = L.map('detail-map').setView([firstLat, firstLng], 13);  
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);

    validLocations.forEach(function(item) {  
        var lat = parseFloat(item.vi_do);  
        var lng = parseFloat(item.kinh_do);  

        var popupContent = `  
            <div style="max-width: 220px;">  
                <h6 style="color: #198754; font-weight: bold; margin-bottom: 5px;">${item.ten_co_so}</h6>  
                <p style="font-size: 13px; margin-bottom: 4px;"><b>Địa chỉ:</b> ${item.dia_chi}</p>  
                ${item.so_dien_thoai ? `<p style="font-size: 13px; margin-bottom: 4px;"><b>SĐT:</b> <a href="tel:${item.so_dien_thoai}">${item.so_dien_thoai}</a></p>` : ''}  
            </div>  
        `;

        var marker = L.marker([lat, lng]).addTo(map).bindPopup(popupContent);  
        markersMap[item.id] = marker;  
        marker.on('click', function() { chonCoSoOnMap(item.id, lat, lng); });  
    });

    chonCoSoOnMap(validLocations[0].id, firstLat, firstLng);  

    setTimeout(function() {
        if (map) map.invalidateSize();
    }, 200);

    const btnFullscreen = document.getElementById('btn-toggle-fullscreen');
    if (btnFullscreen && mapElement) {
        btnFullscreen.addEventListener('click', function () {
            mapElement.classList.toggle('map-fullscreen');

            if (mapElement.classList.contains('map-fullscreen')) {
                btnFullscreen.innerHTML = '<i class="bi bi-fullscreen-exit fs-5 me-2"></i> Thu nhỏ bản đồ';
                btnFullscreen.style.position = 'fixed';
                btnFullscreen.style.top = '20px';
                btnFullscreen.style.right = '20px';
                btnFullscreen.style.zIndex = '10000';
                btnFullscreen.style.padding = '10px 24px';
                btnFullscreen.style.fontSize = '16px';
                btnFullscreen.style.boxShadow = '0 6px 20px rgba(0, 0, 0, 0.35)';
                btnFullscreen.style.borderRadius = '30px';
                btnFullscreen.classList.replace('btn-outline-success', 'btn-danger');
                btnFullscreen.classList.add('fw-bold');
            } else {
                btnFullscreen.innerHTML = '<i class="bi bi-arrows-fullscreen me-1"></i> Phóng to bản đồ';
                btnFullscreen.style.position = 'static';
                btnFullscreen.style.padding = '';
                btnFullscreen.style.fontSize = '';
                btnFullscreen.style.boxShadow = '';
                btnFullscreen.style.borderRadius = '';
                btnFullscreen.classList.replace('btn-danger', 'btn-outline-success');
                btnFullscreen.classList.remove('fw-bold');
            }

            setTimeout(function () { 
                if (typeof map !== 'undefined') {
                    map.invalidateSize(); 
                }
            }, 200);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && mapElement.classList.contains('map-fullscreen')) {
                btnFullscreen.click();
            }
        });
    }
});  
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>