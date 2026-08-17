<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Bản đồ đặc sản & Cơ sở sản xuất Cà Mau';
$currentPage = 'ban-do';

$dacSanId = filter_input(INPUT_GET, 'dac_san_id', FILTER_VALIDATE_INT);
$coSoId = filter_input(INPUT_GET, 'co_so_id', FILTER_VALIDATE_INT);

/*
 * 1. Lấy danh sách danh mục & đặc sản để tạo bộ lọc danh mục bên trái
 */
$stmtDacSan = $pdo->query(
    'SELECT ds.id, ds.ten_dac_san, dm.ten_danh_muc 
     FROM dac_san ds
     LEFT JOIN danh_muc dm ON dm.id = ds.danh_muc_id
     WHERE ds.trang_thai = 1 
     ORDER BY dm.ten_danh_muc ASC, ds.ten_dac_san ASC'
);
$danhSachDacSan = $stmtDacSan->fetchAll();

/*
 * 2. Lấy danh sách cơ sở sản xuất kèm tọa độ
 */
$sql = '
    SELECT 
        cs.id,
        cs.ten_co_so,
        cs.dia_chi,
        cs.so_dien_thoai,
        cs.email,
        cs.hinh_anh,
        cs.vi_do,
        cs.kinh_do,
        cs.google_maps_url,
        GROUP_CONCAT(DISTINCT ds.ten_dac_san ORDER BY ds.ten_dac_san SEPARATOR ", ") AS danh_sach_dac_san
    FROM co_so_san_xuat AS cs
    LEFT JOIN dac_san_co_so AS dscs ON dscs.co_so_id = cs.id
    LEFT JOIN dac_san AS ds ON ds.id = dscs.dac_san_id AND ds.trang_thai = 1
    WHERE cs.trang_thai = 1 
';

$params = [];

if ($dacSanId) {
    $sql .= ' AND EXISTS (
        SELECT 1 FROM dac_san_co_so AS lk 
        WHERE lk.co_so_id = cs.id AND lk.dac_san_id = :dac_san_id
    )';
    $params['dac_san_id'] = $dacSanId;
}

$sql .= ' GROUP BY cs.id ORDER BY cs.ten_co_so ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$danhSachCoSo = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Thư viện bản đồ Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .map-banner {
        padding: 50px 0;
        color: #ffffff;
        text-align: center;
        background: linear-gradient(rgba(56, 16, 21, 0.85), rgba(56, 16, 21, 0.85)),
                    url("<?= htmlspecialchars($baseUrl) ?>/assets/images/banner-ca-mau.jpg") center / cover no-repeat;
    }

    #detail-map {
        height: 560px;
        width: 100%;
        border-radius: 12px;
        border: 1px solid var(--cm-border, #dee2e6);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .facility-scroll-container {
        max-height: 560px;
        overflow-y: auto;
        padding-right: 6px;
    }

    .facility-select-card {
        cursor: pointer;
        border: 2px solid var(--cm-border, #e9ecef);
        border-radius: 10px;
        background: var(--cm-card, #ffffff);
        transition: all 0.25s ease;
    }

    .facility-select-card:hover {
        border-color: var(--cm-red, #641f25);
        background: rgba(100, 31, 37, 0.04);
        transform: translateY(-2px);
    }

    .facility-select-card.active-facility {
        border-color: var(--cm-red, #641f25) !important;
        background: rgba(100, 31, 37, 0.08) !important;
        box-shadow: 0 4px 12px rgba(100, 31, 37, 0.15);
    }

    .map-fullscreen {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        z-index: 9999 !important;
        border-radius: 0 !important;
    }

    @media (max-width: 991px) {
        #detail-map {
            height: 400px;
        }
        .facility-scroll-container {
            max-height: none;
        }
    }
</style>

<section class="map-banner">
    <div class="container">
        <h1 class="fw-bold">Địa điểm & Cơ sở sản xuất</h1>
        <p class="lead mb-0">Chọn một cơ sở trong danh sách bên dưới để xem vị trí chính xác trên bản đồ.</p>
    </div>
</section>

<main class="container py-5">
    <!-- Bộ lọc danh mục đặc sản -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="get" class="row g-2 align-items-center">
                <div class="col-md-8 col-lg-9">
                    <select name="dac_san_id" class="form-select" onchange="this.form.submit()">
                        <option value="">--- Tất cả danh mục đặc sản ---</option>
                        <?php foreach ($danhSachDacSan as $ds): ?>
                            <option value="<?= (int)$ds['id'] ?>" <?= $dacSanId === (int)$ds['id'] ? 'selected' : '' ?>>
                                [<?= htmlspecialchars($ds['ten_danh_muc'] ?? 'Chưa phân loại') ?>] <?= htmlspecialchars($ds['ten_dac_san']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-success flex-grow-1">
                        <i class="bi bi-funnel"></i> Lọc
                    </button>
                    <a href="ban-do.php" class="btn btn-outline-secondary px-3">Xóa lọc</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Khu vực 2 cột: Danh sách bên trái & Bản đồ bên phải -->
    <?php if (empty($danhSachCoSo)): ?>
        <div class="alert alert-warning text-center py-4">
            Không tìm thấy cơ sở sản xuất nào phù hợp với danh mục đặc sản đã chọn.
        </div>
    <?php else: ?>
        <div class="row g-4 align-items-start">
            <!-- CỘT TRÁI: DANH SÁCH CƠ SỞ -->
            <div class="col-lg-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 fw-bold mb-0 text-success">Danh sách cơ sở (<?= count($danhSachCoSo) ?>)</h2>
                </div>

                <div class="facility-scroll-container">
                    <?php foreach ($danhSachCoSo as $coSo): ?>
                        <?php 
                            $viDo = filter_var($coSo['vi_do'], FILTER_VALIDATE_FLOAT);
                            $kinhDo = filter_var($coSo['kinh_do'], FILTER_VALIDATE_FLOAT);
                            $coViTri = ($viDo !== false && $kinhDo !== false && $viDo >= -90 && $viDo <= 90 && $kinhDo >= -180 && $kinhDo <= 180 && ($viDo != 0 || $kinhDo != 0));
                        ?>
                        <div 
                            class="card facility-select-card mb-3 p-3" 
                            id="facility-card-<?= (int)$coSo['id'] ?>"
                            <?php if ($coViTri): ?>
                                onclick="chonCoSoOnMap(<?= (int)$coSo['id'] ?>, <?= (float)$viDo ?>, <?= (float)$kinhDo ?>)"
                            <?php endif; ?>
                        >
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h3 class="h6 fw-bold text-success mb-0"><?= htmlspecialchars($coSo['ten_co_so']) ?></h3>
                                <span class="badge <?= $coViTri ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border' ?>" style="font-size: 11px;">
                                    <?= $coViTri ? 'Có vị trí' : 'Chưa có vị trí' ?>
                                </span>
                            </div>

                            <p class="small text-muted mb-1">
                                <strong>Địa chỉ:</strong> <?= htmlspecialchars($coSo['dia_chi']) ?>
                            </p>

                            <?php if (!empty($coSo['so_dien_thoai'])): ?>
                                <p class="small text-muted mb-2">
                                    <strong>Điện thoại:</strong> 
                                    <a href="tel:<?= htmlspecialchars($coSo['so_dien_thoai']) ?>"><?= htmlspecialchars($coSo['so_dien_thoai']) ?></a>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($coSo['danh_sach_dac_san'])): ?>
                                <p class="small text-muted mb-2">
                                    <strong>Đặc sản:</strong> <?= htmlspecialchars($coSo['danh_sach_dac_san']) ?>
                                </p>
                            <?php endif; ?>

                            <div class="d-flex gap-2 mt-2 pt-2 border-top">
                                <?php if ($coViTri): ?>
                                    <button 
                                        type="button" 
                                        class="btn btn-sm btn-success flex-grow-1" 
                                        onclick="chonCoSoOnMap(<?= (int)$coSo['id'] ?>, <?= (float)$viDo ?>, <?= (float)$kinhDo ?>)"
                                    >
                                        Xem bản đồ
                                    </button>
                                    <a 
                                        href="https://www.google.com/maps/dir/?api=1&destination=<?= $viDo ?>,<?= $kinhDo ?>" 
                                        target="_blank" 
                                        rel="noopener noreferrer" 
                                        class="btn btn-sm btn-outline-primary" 
                                        onclick="event.stopPropagation();"
                                    >
                                        🧭 Chỉ đường
                                    </a>
                                <?php endif; ?>
                                <a 
                                    href="<?= htmlspecialchars(taoGoogleMapsUrl($coSo)) ?>" 
                                    target="_blank" 
                                    rel="noopener noreferrer" 
                                    class="btn btn-sm btn-outline-secondary" 
                                    onclick="event.stopPropagation();"
                                >
                                    Google Maps
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- CỘT PHẢI: BẢN ĐỒ VỊ TRÍ -->
            <div class="col-lg-7">
                <div class="sticky-top" style="top: 100px;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-success">
                            <i class="bi bi-geo-alt-fill"></i> Bản đồ vị trí
                        </span>
                        <button type="button" id="btn-toggle-fullscreen" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-arrows-fullscreen"></i> Phóng to bản đồ
                        </button>
                    </div>

                    <div id="detail-map"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<script>
var map;
var markersMap = {};

function chonCoSoOnMap(id, lat, lng) {
    document.querySelectorAll('.facility-select-card').forEach(function(card) {
        card.classList.remove('active-facility');
    });

    var selectedCard = document.getElementById('facility-card-' + id);
    if (selectedCard) {
        selectedCard.classList.add('active-facility');
    }

    if (map && markersMap[id]) {
        map.setView([lat, lng], 14, { animate: true });
        markersMap[id].openPopup();
    }
}

document.addEventListener("DOMContentLoaded", function() {
    var coSoList = <?= json_encode($danhSachCoSo, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var selectedCoSoId = <?= json_encode($coSoId); ?>;

    var validLocations = coSoList.filter(function(item) {
        var lat = parseFloat(item.vi_do);
        var lng = parseFloat(item.kinh_do);
        return !isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180 && (lat !== 0 || lng !== 0);
    });

    var mapElement = document.getElementById('detail-map');
    if (!mapElement) return;

    if (validLocations.length === 0) {
        mapElement.innerHTML = '<div class="p-5 text-center text-muted">Chưa có thông tin tọa độ bản đồ hợp lệ cho các cơ sở này.</div>';
        return;
    }

    // Thiết lập vị trí ban đầu
    var defaultLat = 9.176;
    var defaultLng = 105.15;
    var defaultZoom = 10;

    map = L.map('detail-map').setView([defaultLat, defaultLng], defaultZoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    var targetMarker = null;

    validLocations.forEach(function(item) {
        var lat = parseFloat(item.vi_do);
        var lng = parseFloat(item.kinh_do);

        var popupContent = `
            <div style="max-width: 230px;">
                <h6 style="color: #641f25; font-weight: bold; margin-bottom: 6px;">${item.ten_co_so}</h6>
                <p style="font-size: 13px; margin-bottom: 4px;"><b>Địa chỉ:</b> ${item.dia_chi}</p>
                ${item.so_dien_thoai ? `<p style="font-size: 13px; margin-bottom: 4px;"><b>SĐT:</b> <a href="tel:${item.so_dien_thoai}">${item.so_dien_thoai}</a></p>` : ''}
                ${item.danh_sach_dac_san ? `<p style="font-size: 13px; margin-bottom: 4px;"><b>Đặc sản:</b> ${item.danh_sach_dac_san}</p>` : ''}
            </div>
        `;

        var marker = L.marker([lat, lng]).addTo(map).bindPopup(popupContent);
        markersMap[item.id] = marker;

        marker.on('click', function() {
            chonCoSoOnMap(item.id, lat, lng);
        });

        if (selectedCoSoId && parseInt(item.id) === parseInt(selectedCoSoId)) {
            targetMarker = { id: item.id, lat: lat, lng: lng };
        }
    });

    // Mở cơ sở được chọn từ URL hoặc mặc định cơ sở đầu tiên
    if (targetMarker) {
        chonCoSoOnMap(targetMarker.id, targetMarker.lat, targetMarker.lng);
    } else if (validLocations.length > 0) {
        var first = validLocations[0];
        chonCoSoOnMap(first.id, parseFloat(first.vi_do), parseFloat(first.kinh_do));
    }

    setTimeout(function() {
        if (map) map.invalidateSize();
    }, 200);

    // Chức năng Phóng to / Thu nhỏ bản đồ toàn màn hình
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
                btnFullscreen.style.fontSize = '15px';
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
            }, 250);
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