<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$pageTitle = 'Bản đồ đặc sản & Cơ sở sản xuất Cà Mau';
$currentPage = 'ban-do'; // Đảm bảo Active đúng menu trên Navbar

$dacSanId = filter_input(INPUT_GET, 'dac_san_id', FILTER_VALIDATE_INT);
$coSoId = filter_input(INPUT_GET, 'co_so_id', FILTER_VALIDATE_INT);

/*
 * Lấy danh sách đặc sản để làm bộ lọc
 */
$stmtDacSan = $pdo->query(
    'SELECT id, ten_dac_san 
     FROM dac_san 
     WHERE trang_thai = 1 
     ORDER BY ten_dac_san ASC'
);
$danhSachDacSan = $stmtDacSan->fetchAll();

/*
 * Lấy danh sách cơ sở sản xuất có tọa độ
 */
$sql = '
    SELECT 
        cs.id,
        cs.ten_co_so,
        cs.dia_chi,
        cs.so_dien_thoai,
        cs.hinh_anh,
        cs.vi_do,
        cs.kinh_do,
        GROUP_CONCAT(DISTINCT ds.ten_dac_san ORDER BY ds.ten_dac_san SEPARATOR ", ") AS danh_sach_dac_san
    FROM co_so_san_xuat AS cs
    LEFT JOIN dac_san_co_so AS dscs ON dscs.co_so_id = cs.id
    LEFT JOIN dac_san AS ds ON ds.id = dscs.dac_san_id AND ds.trang_thai = 1
    WHERE cs.trang_thai = 1 
      AND cs.vi_do IS NOT NULL 
      AND cs.kinh_do IS NOT NULL
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

<!-- Thêm thư viện bản đồ Leaflet (CSS & JS) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .map-banner {
        padding: 50px 0;
        color: #ffffff;
        text-align: center;
        background: linear-gradient(rgba(20, 77, 54, 0.85), rgba(20, 77, 54, 0.85)),
                    url("/dac-san-ca-mau/assets/images/banner-ca-mau.jpg") center / cover no-repeat;
    }
    #map {
        height: 600px;
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }
</style>

<section class="map-banner">
    <div class="container">
        <h1 class="fw-bold">Bản đồ đặc sản Cà Mau</h1>
        <p class="lead mb-0">Tra cứu vị trí các cơ sở sản xuất đặc sản trên địa bàn tỉnh Cà Mau</p>
    </div>
</section>

<main class="container py-5">
    <!-- Bộ lọc theo đặc sản -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="get" class="row g-3 align-items-center">
            <div class="col-md-7 col-lg-8">
                <select name="dac_san_id" class="form-select" onchange="this.form.submit()">
                    <option value="">--- Tất cả đặc sản ---</option>
                    <?php foreach ($danhSachDacSan as $ds): ?>
                        <option value="<?= (int)$ds['id'] ?>" <?= $dacSanId === (int)$ds['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ds['ten_dac_san']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5 col-lg-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success flex-grow-1 text-nowrap">
                        <i class="bi bi-funnel"></i> Lọc vị trí
                    </button>
                    <a href="/dac-san-ca-mau/ban-do.php" class="btn btn-outline-secondary text-nowrap px-3">
                        Xóa bộ lọc
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

    <!-- Khung bản đồ -->
    <div id="map"></div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Tọa độ trung tâm Cà Mau (Vĩ độ: 9.176, Kinh độ: 105.15)
    var defaultLat = 9.176;
    var defaultLng = 105.15;
    var defaultZoom = 10;

    // Dữ liệu danh sách cơ sở từ PHP
    var locations = <?= json_encode($danhSachCoSo, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var selectedCoSoId = <?= json_encode($coSoId); ?>;

    // Khởi tạo bản đồ Leaflet
    var map = L.map('map').setView([defaultLat, defaultLng], defaultZoom);

    // Thêm bản đồ OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    var selectedMarker = null;

    // Duyệt danh sách và cắm ghim (Marker)
    locations.forEach(function(item) {
        if (item.vi_do && item.kinh_do) {
            var lat = parseFloat(item.vi_do);
            var lng = parseFloat(item.kinh_do);

            var popupContent = `
                <div style="max-width: 220px;">
                    <h6 style="color: #144d36; font-weight: bold; margin-bottom: 5px;">${item.ten_co_so}</h6>
                    <p style="font-size: 13px; margin-bottom: 5px;"><b>Địa chỉ:</b> ${item.dia_chi}</p>
                    ${item.so_dien_thoai ? `<p style="font-size: 13px; margin-bottom: 5px;"><b>ĐIện thoại:</b> ${item.so_dien_thoai}</p>` : ''}
                    ${item.danh_sach_dac_san ? `<p style="font-size: 13px; margin-bottom: 5px;"><b>Sản phẩm:</b> ${item.danh_sach_dac_san}</p>` : ''}
                </div>
            `;

            var marker = L.marker([lat, lng]).addTo(map).bindPopup(popupContent);

            // Nếu trang được gọi từ nút "Vị trí" của 1 cơ sở cụ thể
            if (selectedCoSoId && parseInt(item.id) === parseInt(selectedCoSoId)) {
                selectedMarker = marker;
                map.setView([lat, lng], 14); // Zoom vào cơ sở đó
            }
        }
    });

    if (selectedMarker) {
        selectedMarker.openPopup();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>