<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';        
require_once __DIR__ . '/../../config/database.php';    
require_once __DIR__ . '/../../includes/functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {        
    $_SESSION['error'] = 'Mã cơ sở không hợp lệ.';        
    header('Location: index.php');        
    exit;        
}

$stmt = $pdo->prepare('SELECT * FROM co_so_san_xuat WHERE id = :id LIMIT 1');    
$stmt->execute(['id' => $id]);    
$coSo = $stmt->fetch();

if (!$coSo) {        
    $_SESSION['error'] = 'Không tìm thấy cơ sở.';        
    header('Location: index.php');        
    exit;        
}

/* Lấy danh sách đặc sản hiện tại đang liên kết với cơ sở này */
$stmtLinked = $pdo->prepare('SELECT dac_san_id FROM dac_san_co_so WHERE co_so_id = :co_so_id');  
$stmtLinked->execute(['co_so_id' => $id]);  
$dacSanDaChon = $stmtLinked->fetchAll(PDO::FETCH_COLUMN);

/* Lấy toàn bộ đặc sản kèm danh mục để hiển thị gom nhóm */
$stmtAllDacSan = $pdo->query('  
    SELECT ds.id, ds.ten_dac_san, dm.ten_danh_muc  
    FROM dac_san ds  
    LEFT JOIN danh_muc dm ON ds.danh_muc_id = dm.id  
    ORDER BY dm.ten_danh_muc ASC, ds.ten_dac_san ASC  
');  
$allDacSan = $stmtAllDacSan->fetchAll();

$dacSanTheoDanhMuc = [];  
foreach ($allDacSan as $item) {  
    $tenDm = $item['ten_danh_muc'] ?? 'Chưa phân loại';  
    $dacSanTheoDanhMuc[$tenDm][] = $item;  
}

$loi = [];    
$tenCoSo = $coSo['ten_co_so'];        
$slug = $coSo['slug'];        
$diaChi = $coSo['dia_chi'];        
$soDienThoai = $coSo['so_dien_thoai'] ?? '';        
$email = $coSo['email'] ?? '';        
$moTa = $coSo['mo_ta'] ?? '';        
$viDoRaw = (string) ($coSo['vi_do'] ?? '');        
$kinhDoRaw = (string) ($coSo['kinh_do'] ?? '');        
$googleMapsUrl = $coSo['google_maps_url'] ?? '';        
$hinhAnhCu = $coSo['hinh_anh'] ?? null;        
$trangThai = (int) $coSo['trang_thai'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {        
    $tenCoSo = trim($_POST['ten_co_so'] ?? '');        
    $slug = taoSlug($_POST['slug'] ?? '');        
    $diaChi = trim($_POST['dia_chi'] ?? '');        
    $soDienThoai = trim($_POST['so_dien_thoai'] ?? '');        
    $email = trim($_POST['email'] ?? '');        
    $moTa = trim($_POST['mo_ta'] ?? '');        
    $viDoRaw = trim($_POST['vi_do'] ?? '');        
    $kinhDoRaw = trim($_POST['kinh_do'] ?? '');        
    $googleMapsUrl = trim($_POST['google_maps_url'] ?? '');        
    $trangThai = isset($_POST['trang_thai']) ? 1 : 0;  
     
    $dacSanIds = isset($_POST['dac_san_ids']) && is_array($_POST['dac_san_ids'])  
        ? array_map('intval', $_POST['dac_san_ids'])  
        : [];  
    $dacSanDaChon = $dacSanIds;

    if ($tenCoSo === '') $loi[] = 'Vui lòng nhập tên cơ sở.';        
    if ($diaChi === '') $loi[] = 'Vui lòng nhập địa chỉ.';        
    if ($slug === '') $slug = taoSlug($tenCoSo);        
    if ($slug === '') $loi[] = 'Đường dẫn cơ sở không hợp lệ.';    

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $loi[] = 'Địa chỉ email không hợp lệ.';        
    if ($googleMapsUrl !== '' && !filter_var($googleMapsUrl, FILTER_VALIDATE_URL)) $loi[] = 'Đường dẫn Google Maps không hợp lệ.';    

    if ($googleMapsUrl !== '' && ($viDoRaw === '' || $kinhDoRaw === '')) {      
        $toaDoTuAuto = layToaDoTuGoogleMapsUrl($googleMapsUrl);      
        if ($viDoRaw === '' && $toaDoTuAuto['vi_do'] !== null) $viDoRaw = (string)$toaDoTuAuto['vi_do'];      
        if ($kinhDoRaw === '' && $toaDoTuAuto['kinh_do'] !== null) $kinhDoRaw = (string)$toaDoTuAuto['kinh_do'];      
    }

    $viDo = ($viDoRaw !== '') ? filter_var($viDoRaw, FILTER_VALIDATE_FLOAT) : null;      
    $kinhDo = ($kinhDoRaw !== '') ? filter_var($kinhDoRaw, FILTER_VALIDATE_FLOAT) : null;

    $kiemTraTrung = $pdo->prepare('SELECT id FROM co_so_san_xuat WHERE (ten_co_so = :ten_co_so OR slug = :slug) AND id <> :id LIMIT 1');    
    $kiemTraTrung->execute(['ten_co_so' => $tenCoSo, 'slug' => $slug, 'id' => $id]);    
    if ($kiemTraTrung->fetch()) $loi[] = 'Tên cơ sở hoặc đường dẫn đã tồn tại.';    

    $tenHinhAnhMoi = null; $duongDanAnhMoi = null;    
    if (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] !== UPLOAD_ERR_NO_FILE) {        
        $file = $_FILES['hinh_anh'];    
        if ($file['error'] !== UPLOAD_ERR_OK) { $loi[] = 'Có lỗi khi tải hình ảnh.'; }    
        elseif ($file['size'] > 5 * 1024 * 1024) { $loi[] = 'Hình ảnh không được vượt quá 5 MB.'; }    
        else {        
            $thongTinAnh = @getimagesize($file['tmp_name']);    
            $loaiAnhChoPhep = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];    
            $mimeType = $thongTinAnh['mime'] ?? '';    
            if (!isset($loaiAnhChoPhep[$mimeType])) { $loi[] = 'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.'; }    
            else {        
                $tenHinhAnhMoi = sprintf('%s-%s.%s', $slug, bin2hex(random_bytes(4)), $loaiAnhChoPhep[$mimeType]);    
                $thuMucUpload = __DIR__ . '/../../assets/uploads/co-so/';    
                if (!is_dir($thuMucUpload)) mkdir($thuMucUpload, 0777, true);        
                $duongDanAnhMoi = $thuMucUpload . $tenHinhAnhMoi;        
            }        
        }        
    }

    if (empty($loi)) {        
        try {        
            $pdo->beginTransaction();

            if ($tenHinhAnhMoi !== null && $duongDanAnhMoi !== null) {        
                if (!move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $duongDanAnhMoi)) {        
                    throw new RuntimeException('Không thể lưu hình ảnh mới.');        
                }        
            }

            $hinhAnhLuu = $tenHinhAnhMoi ?? $hinhAnhCu;    
            $capNhat = $pdo->prepare('UPDATE co_so_san_xuat SET ten_co_so = :ten_co_so, slug = :slug, dia_chi = :dia_chi, so_dien_thoai = :so_dien_thoai, email = :email, mo_ta = :mo_ta, hinh_anh = :hinh_anh, vi_do = :vi_do, kinh_do = :kinh_do, google_maps_url = :google_maps_url, trang_thai = :trang_thai WHERE id = :id');    
            $capNhat->execute([        
                'ten_co_so' => $tenCoSo, 'slug' => $slug, 'dia_chi' => $diaChi,    
                'so_dien_thoai' => $soDienThoai !== '' ? $soDienThoai : null,    
                'email' => $email !== '' ? $email : null,    
                'mo_ta' => $moTa !== '' ? $moTa : null,    
                'hinh_anh' => $hinhAnhLuu, 'vi_do' => $viDo, 'kinh_do' => $kinhDo,    
                'google_maps_url' => $googleMapsUrl !== '' ? $googleMapsUrl : null,    
                'trang_thai' => $trangThai, 'id' => $id        
            ]);

            $xoaLienKet = $pdo->prepare('DELETE FROM dac_san_co_so WHERE co_so_id = :co_so_id');  
            $xoaLienKet->execute(['co_so_id' => $id]);

            if (!empty($dacSanIds)) {  
                $themDsCs = $pdo->prepare('INSERT INTO dac_san_co_so (dac_san_id, co_so_id) VALUES (:dac_san_id, :co_so_id)');  
                foreach ($dacSanIds as $dsId) {  
                    $themDsCs->execute(['dac_san_id' => $dsId, 'co_so_id' => $id]);  
                }  
            }

            $pdo->commit();

            if ($tenHinhAnhMoi !== null && !empty($hinhAnhCu)) {        
                $duongDanAnhCu = __DIR__ . '/../../assets/uploads/co-so/' . basename($hinhAnhCu);    
                if (is_file($duongDanAnhCu)) unlink($duongDanAnhCu);        
            }

            $_SESSION['success'] = 'Cập nhật cơ sở thành công.';    
            header('Location: index.php');        
            exit;        
        } catch (Throwable $e) {        
            if ($pdo->inTransaction()) $pdo->rollBack();  
            if ($duongDanAnhMoi !== null && is_file($duongDanAnhMoi)) unlink($duongDanAnhMoi);        
            $loi[] = 'Không thể cập nhật cơ sở: ' . $e->getMessage();        
        }        
    }        
}        
?>    
<!DOCTYPE html>        
<html lang="vi">        
<head>        
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">        
    <title>Sửa cơ sở sản xuất</title>        
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">        
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>        
        .current-image, #imagePreview { width: 100%; max-width: 280px; height: 180px; object-fit: cover; border-radius: 8px; }      
        #imagePreview { display: none; }  
        .dac-san-box { max-height: 250px; overflow-y: auto; background: #fff; border: 1px solid #dee2e6; border-radius: 6px; padding: 12px; }  
    </style>        
</head>      
<body class="bg-light">        
    <nav class="navbar navbar-dark bg-success">        
        <div class="container">        
            <a class="navbar-brand fw-bold" href="/DuAnNgheCoBan_Nhom1/admin/index.php">Quản trị đặc sản Cà Mau</a>        
            <div class="d-flex align-items-center gap-2">
                <a href="/DuAnNgheCoBan_Nhom1/index.php" target="_blank" class="btn btn-light btn-sm fw-semibold">🌐 Xem trang chủ</a>
                <a href="/DuAnNgheCoBan_Nhom1/logout.php" class="btn btn-outline-light btn-sm">Đăng xuất</a>        
            </div>  
        </div>        
    </nav>      
    <main class="container py-5">        
        <div class="card border-0 shadow-sm">        
            <div class="card-body p-4">        
                <div class="d-flex justify-content-between align-items-center mb-4">        
                    <h1 class="h3 mb-0">Sửa cơ sở sản xuất</h1>        
                    <a href="index.php" class="btn btn-secondary">Quay lại danh sách</a>        
                </div>      
                <?php if (!empty($loi)): ?>        
                    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($loi as $n) echo "<li>".htmlspecialchars($n)."</li>"; ?></ul></div>        
                <?php endif; ?>      
                <form method="post" enctype="multipart/form-data">        
                    <div class="row g-4">        
                        <div class="col-lg-8">        
                            <div class="mb-3">        
                                <label for="ten_co_so" class="form-label">Tên cơ sở <span class="text-danger">*</span></label>        
                                <input type="text" id="ten_co_so" name="ten_co_so" class="form-control" value="<?= htmlspecialchars($tenCoSo) ?>" required>        
                            </div>      
                            <div class="mb-3">        
                                <label for="slug" class="form-label">Đường dẫn</label>        
                                <input type="text" id="slug" name="slug" class="form-control" value="<?= htmlspecialchars($slug) ?>">        
                            </div>      
                            <div class="mb-3">        
                                <label for="dia_chi" class="form-label">Địa chỉ <span class="text-danger">*</span></label>        
                                <input type="text" id="dia_chi" name="dia_chi" class="form-control" value="<?= htmlspecialchars($diaChi) ?>" required>        
                            </div>      
                            <div class="row">        
                                <div class="col-md-6 mb-3">        
                                    <label for="so_dien_thoai" class="form-label">Số điện thoại</label>        
                                    <input type="text" id="so_dien_thoai" name="so_dien_thoai" class="form-control" value="<?= htmlspecialchars($soDienThoai) ?>">        
                                </div>      
                                <div class="col-md-6 mb-3">        
                                    <label for="email" class="form-label">Email</label>        
                                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>">        
                                </div>        
                            </div>      
                            <div class="mb-3">        
                                <label for="mo_ta" class="form-label">Mô tả</label>        
                                <textarea id="mo_ta" name="mo_ta" class="form-control" rows="5"><?= htmlspecialchars($moTa) ?></textarea>        
                            </div>    

                            <div class="mb-4">  
                                <label class="form-label fw-bold text-success">Đặc sản cung cấp / kinh doanh:</label>  
                                <div class="dac-san-box">  
                                    <?php if (empty($dacSanTheoDanhMuc)): ?>  
                                        <p class="text-muted mb-0 small">Chưa có đặc sản nào trên hệ thống.</p>  
                                    <?php else: ?>  
                                        <?php foreach ($dacSanTheoDanhMuc as $tenDanhMuc => $dsList): ?>  
                                            <div class="mb-2">  
                                                <strong class="d-block text-primary small mb-1 border-bottom pb-1"><?= htmlspecialchars($tenDanhMuc) ?></strong>  
                                                <div class="row">  
                                                    <?php foreach ($dsList as $ds): ?>  
                                                        <div class="col-md-6">  
                                                            <div class="form-check">  
                                                                <input class="form-check-input" type="checkbox" name="dac_san_ids[]" value="<?= (int)$ds['id'] ?>" id="ds_<?= (int)$ds['id'] ?>" <?= in_array((int)$ds['id'], $dacSanDaChon, true) ? 'checked' : '' ?>>  
                                                                <label class="form-check-label small" for="ds_<?= (int)$ds['id'] ?>">  
                                                                    <?= htmlspecialchars($ds['ten_dac_san']) ?>  
                                                                </label>  
                                                            </div>  
                                                        </div>  
                                                    <?php endforeach; ?>  
                                                </div>  
                                            </div>  
                                        <?php endforeach; ?>  
                                    <?php endif; ?>  
                                </div>  
                                <div class="form-text">Tích chọn các đặc sản mà cơ sở này sản xuất hoặc phân phối.</div>  
                            </div>

                            <h2 class="h5 text-success mt-4">Thông tin vị trí & Bản đồ</h2>      
                            <div class="mb-3">        
                                <label for="google_maps_url" class="form-label">Đường dẫn Google Maps</label>        
                                <input type="url" id="google_maps_url" name="google_maps_url" class="form-control" value="<?= htmlspecialchars($googleMapsUrl) ?>" placeholder="https://maps.app.goo.gl/...">        
                            </div>      
                            <div class="row mb-3">        
                                <div class="col-md-6">        
                                    <label for="vi_do" class="form-label">Vĩ độ (Latitude)</label>        
                                    <input type="number" step="any" id="vi_do" name="vi_do" class="form-control" value="<?= htmlspecialchars($viDoRaw) ?>">        
                                </div>      
                                <div class="col-md-6">        
                                    <label for="kinh_do" class="form-label">Kinh độ (Longitude)</label>        
                                    <input type="number" step="any" id="kinh_do" name="kinh_do" class="form-control" value="<?= htmlspecialchars($kinhDoRaw) ?>">        
                                </div>        
                            </div>

                            <!-- Khung bản đồ chọn tọa độ trực quan -->
                            <div class="mb-3">
                                <label class="form-label fw-bold text-success">Chọn vị trí trực tiếp trên bản đồ:</label>
                                <div id="admin-map" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #dee2e6;"></div>
                                <div class="form-text">Nhấp vào vị trí bất kỳ trên bản đồ hoặc kéo thả ghim để tự động lấy tọa độ.</div>
                            </div>
                        </div>      

                        <div class="col-lg-4">        
                            <?php if (!empty($hinhAnhCu)): ?>        
                                <div class="mb-3">        
                                    <label class="form-label">Hình ảnh hiện tại</label>        
                                    <div><img src="/DuAnNgheCoBan_Nhom1/assets/uploads/co-so/<?= htmlspecialchars($hinhAnhCu) ?>" alt="<?= htmlspecialchars($tenCoSo) ?>" class="current-image"></div>        
                                </div>        
                            <?php endif; ?>      
                            <div class="mb-3">        
                                <label for="hinh_anh" class="form-label">Chọn hình ảnh mới</label>        
                                <input type="file" id="hinh_anh" name="hinh_anh" class="form-control" accept=".jpg,.jpeg,.png,.webp">        
                            </div>      
                            <img id="imagePreview" src="" alt="Ảnh mới" class="mb-4">      
                            <div class="form-check mb-4">        
                                <input type="checkbox" id="trang_thai" name="trang_thai" class="form-check-input" <?= $trangThai === 1 ? 'checked' : '' ?>>        
                                <label for="trang_thai" class="form-check-label">Hiển thị trên website</label>        
                            </div>      
                            <button type="submit" class="btn btn-success w-100">Cập nhật cơ sở</button>        
                        </div>        
                    </div>        
                </form>        
            </div>        
        </div>        
    </main>      

    <script>        
        const imageInput = document.getElementById('hinh_anh');        
        const imagePreview = document.getElementById('imagePreview');      
        imageInput.addEventListener('change', function () {        
            const file = this.files[0];      
            if (!file) { imagePreview.src = ''; imagePreview.style.display = 'none'; return; }      
            imagePreview.src = URL.createObjectURL(file);        
            imagePreview.style.display = 'block';        
        });    

        const googleMapsInput = document.getElementById('google_maps_url');      
        const viDoInput = document.getElementById('vi_do');      
        const kinhDoInput = document.getElementById('kinh_do');

        let defaultLat = parseFloat(viDoInput.value) || 9.1768;
        let defaultLng = parseFloat(kinhDoInput.value) || 105.1524;
        let hasLocation = viDoInput.value !== '' && kinhDoInput.value !== '';

        const adminMap = L.map('admin-map').setView([defaultLat, defaultLng], hasLocation ? 14 : 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(adminMap);

        let marker;
        function updateMarker(lat, lng) {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], { draggable: true }).addTo(adminMap);
                marker.on('dragend', function (e) {
                    const pos = e.target.getLatLng();
                    viDoInput.value = pos.lat.toFixed(7);
                    kinhDoInput.value = pos.lng.toFixed(7);
                });
            }
            adminMap.setView([lat, lng], 14);
        }

        if (hasLocation) {
            updateMarker(defaultLat, defaultLng);
        }

        adminMap.on('click', function (e) {
            viDoInput.value = e.latlng.lat.toFixed(7);
            kinhDoInput.value = e.latlng.lng.toFixed(7);
            updateMarker(e.latlng.lat, e.latlng.lng);
        });

        if (googleMapsInput) {      
            googleMapsInput.addEventListener('input', function () {      
                let url = decodeURIComponent(this.value.trim());      
                if (!url) return;    
                let matchAt = url.match(/@(-?[0-9]+\.[0-9]+),(-?[0-9]+\.[0-9]+)/);      
                let match3d = url.match(/!3d(-?[0-9]+\.[0-9]+)!4d(-?[0-9]+\.[0-9]+)/);      
                let matchQuery = url.match(/[?&](?:q|query|center)=(-?[0-9]+\.[0-9]+),(-?[0-9]+\.[0-9]+)/);    
                let res = matchAt || match3d || matchQuery;      
                if (res && res[1] && res[2]) {      
                    viDoInput.value = res[1];      
                    kinhDoInput.value = res[2];
                    updateMarker(parseFloat(res[1]), parseFloat(res[2]));      
                }      
            });      
        }

        setTimeout(() => adminMap.invalidateSize(), 200);
    </script>        
</body>        
</html>