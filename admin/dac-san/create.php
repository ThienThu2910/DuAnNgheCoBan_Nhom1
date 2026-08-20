<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$thongBao = $_SESSION['success'] ?? '';
$loi = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// 1. Lấy danh sách danh mục
$stmtDanhMuc = $pdo->query('SELECT id, ten_danh_muc FROM danh_muc WHERE trang_thai = 1 ORDER BY thu_tu ASC, ten_danh_muc ASC');
$danhSachDanhMuc = $stmtDanhMuc->fetchAll();

// 2. Kiểm tra danh sách các cột thực tế đang có trong bảng dac_san
$existingCols = [];
try {
    $existingCols = $pdo->query('SHOW COLUMNS FROM dac_san')->fetchAll(PDO::FETCH_COLUMN);
} catch (\PDOException $e) {
    // Bỏ qua nếu lỗi
}

$tenDacSan = '';
$danhMucId = null;
$moTaNgan = '';
$noiDungChiTiet = '';
$noiBat = 0;
$trangThai = 1;

// 3. Xử lý lưu form thêm mới
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDacSan = trim($_POST['ten_dac_san'] ?? '');
    $danhMucId = filter_input(INPUT_POST, 'danh_muc_id', FILTER_VALIDATE_INT) ?: null;
    $moTaNgan = trim($_POST['mo_ta_ngan'] ?? '');
    $noiDungChiTiet = trim($_POST['noi_dung_chi_tiet'] ?? '');
    $noiBat = isset($_POST['noi_bat']) ? 1 : 0;
    $trangThai = isset($_POST['trang_thai']) ? 1 : 0;

    if ($tenDacSan === '') {
        $loi = 'Vui lòng nhập tên đặc sản.';
    } else {
        $uploadDir = __DIR__ . '/../../assets/uploads/dac-san/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Tải ảnh đại diện chính
        $hinhAnhChinh = null;
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!empty($_FILES['hinh_anh']['name']) && $_FILES['hinh_anh']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['hinh_anh']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed, true)) {
                $hinhAnhChinh = 'main_' . time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $uploadDir . $hinhAnhChinh);
            }
        }

        $slug = function_exists('taoSlug') ? taoSlug($tenDacSan) : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $tenDacSan), '-'));

        // Xây dựng câu lệnh INSERT động theo đúng các cột có trong Database
        $fields = ['ten_dac_san', 'danh_muc_id', 'hinh_anh', 'noi_bat', 'trang_thai'];
        $placeholders = [':ten', ':dm_id', ':anh', ':noi_bat', ':trang_thai'];
        $params = [
            'ten' => $tenDacSan,
            'dm_id' => $danhMucId,
            'anh' => $hinhAnhChinh,
            'noi_bat' => $noiBat,
            'trang_thai' => $trangThai
        ];

        if (in_array('slug', $existingCols, true)) {
            $fields[] = 'slug';
            $placeholders[] = ':slug';
            $params['slug'] = $slug;
        }

        if (in_array('mo_ta_ngan', $existingCols, true)) {
            $fields[] = 'mo_ta_ngan';
            $placeholders[] = ':mo_ta';
            $params['mo_ta'] = $moTaNgan;
        }

        // Kiểm tra cột nội dung chi tiết
        if (in_array('noi_dung_chi_tiet', $existingCols, true)) {
            $fields[] = 'noi_dung_chi_tiet';
            $placeholders[] = ':noi_dung';
            $params['noi_dung'] = $noiDungChiTiet;
        } elseif (in_array('noi_dung', $existingCols, true)) {
            $fields[] = 'noi_dung';
            $placeholders[] = ':noi_dung';
            $params['noi_dung'] = $noiDungChiTiet;
        } elseif (in_array('chi_tiet', $existingCols, true)) {
            $fields[] = 'chi_tiet';
            $placeholders[] = ':noi_dung';
            $params['noi_dung'] = $noiDungChiTiet;
        }

        if (in_array('ngay_tao', $existingCols, true)) {
            $fields[] = 'ngay_tao';
            $placeholders[] = 'NOW()';
        }

        $sql = 'INSERT INTO dac_san (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmtInsert = $pdo->prepare($sql);
        $stmtInsert->execute($params);

        $dacSanId = (int)$pdo->lastInsertId();

        // Xử lý tải lên nhiều hình ảnh phụ cùng lúc
        if ($dacSanId > 0 && !empty($_FILES['anh_phu']['name'][0])) {
            $colName = 'hinh_anh';
            try {
                $subCols = $pdo->query('SHOW COLUMNS FROM hinh_anh_dac_san')->fetchAll(PDO::FETCH_COLUMN);
                if (in_array('duong_dan_anh', $subCols, true)) {
                    $colName = 'duong_dan_anh';
                } elseif (in_array('duong_dan', $subCols, true)) {
                    $colName = 'duong_dan';
                }
            } catch (\PDOException $e) {
                $pdo->exec('CREATE TABLE IF NOT EXISTS hinh_anh_dac_san (id INT AUTO_INCREMENT PRIMARY KEY, dac_san_id INT, hinh_anh VARCHAR(255))');
            }

            $totalFiles = count($_FILES['anh_phu']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['anh_phu']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['anh_phu']['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed, true)) {
                        $subFileName = 'sub_' . time() . '_' . uniqid() . '.' . $ext;
                        if (move_uploaded_file($_FILES['anh_phu']['tmp_name'][$i], $uploadDir . $subFileName)) {
                            try {
                                $stmtSub = $pdo->prepare("INSERT INTO hinh_anh_dac_san (dac_san_id, {$colName}) VALUES (:dac_san_id, :file_name)");
                                $stmtSub->execute([
                                    'dac_san_id' => $dacSanId,
                                    'file_name' => $subFileName
                                ]);
                            } catch (\PDOException $e) {
                                // Bỏ qua nếu lỗi bảng ảnh
                            }
                        }
                    }
                }
            }
        }

        $_SESSION['success'] = 'Thêm mới đặc sản thành công!';
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm mới đặc sản - Quản trị Cà Mau</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .admin-form-card { max-width: 900px; margin: 30px auto; border: 0; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
        .gallery-upload-box { background-color: #fcf8f8; border: 1px dashed #dc3545; border-radius: 8px; padding: 20px; }
    </style>
</head>
<body class="p-3 p-md-4">

<div class="card admin-form-card">
    <div class="card-body p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <div>
                <h2 class="h4 fw-bold mb-1">Thêm mới đặc sản</h2>
                <p class="text-muted small mb-0">Thêm thông tin đặc sản và hình ảnh trưng bày chi tiết.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Danh sách đặc sản
            </a>
        </div>

        <?php if ($loi !== ''): ?>
            <div class="alert alert-danger mb-4"><?= htmlspecialchars($loi) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="ten_dac_san" class="form-label fw-semibold">Tên đặc sản <span class="text-danger">*</span></label>
                    <input 
                        type="text" 
                        id="ten_dac_san" 
                        name="ten_dac_san" 
                        class="form-control" 
                        value="<?= htmlspecialchars($tenDacSan) ?>" 
                        placeholder="Ví dụ: Cua biển Năm Căn, Mật ong rừng U Minh..." 
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label for="danh_muc_id" class="form-label fw-semibold">Danh mục</label>
                    <select id="danh_muc_id" name="danh_muc_id" class="form-select">
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($danhSachDanhMuc as $dm): ?>
                            <option value="<?= (int)$dm['id'] ?>" <?= $danhMucId === (int)$dm['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dm['ten_danh_muc']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label for="hinh_anh" class="form-label fw-semibold">Ảnh đại diện chính</label>
                    <input type="file" id="hinh_anh" name="hinh_anh" class="form-control" accept="image/*">
                </div>

                <!-- TẢI LÊN NHIỀU ẢNH PHỤ -->
                <div class="col-12">
                    <div class="gallery-upload-box">
                        <label for="anh_phu" class="form-label fw-bold text-danger mb-1">
                            <i class="bi bi-images me-1"></i> Bộ sưu tập hình ảnh phụ (Tải nhiều ảnh cùng lúc)
                        </label>
                        <p class="small text-muted mb-2">Tải thêm các góc chụp thực tế, quy trình sản xuất, đóng gói...</p>
                        <input 
                            type="file" 
                            id="anh_phu" 
                            name="anh_phu[]" 
                            class="form-control" 
                            multiple 
                            accept="image/*"
                        >
                        <div class="form-text mt-2 text-dark">
                            <i class="bi bi-info-circle me-1"></i> Giữ phím <strong>Ctrl</strong> khi chọn file để chọn nhiều ảnh cùng lúc.
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <label for="mo_ta_ngan" class="form-label fw-semibold">Mô tả tóm tắt ngắn</label>
                    <textarea id="mo_ta_ngan" name="mo_ta_ngan" class="form-control" rows="3"><?= htmlspecialchars($moTaNgan) ?></textarea>
                </div>

                <div class="col-12">
                    <label for="noi_dung_chi_tiet" class="form-label fw-semibold">Nội dung giới thiệu chi tiết</label>
                    <textarea id="noi_dung_chi_tiet" name="noi_dung_chi_tiet" class="form-control" rows="6"><?= htmlspecialchars($noiDungChiTiet) ?></textarea>
                </div>

                <div class="col-12">
                    <div class="d-flex gap-4 p-3 bg-light rounded border">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="noi_bat" value="1" id="noi_bat" <?= $noiBat === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="noi_bat">Đặc sản nổi bật</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="trang_thai" value="1" id="trang_thai" <?= $trangThai === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="trang_thai">Hiển thị công khai ra website</label>
                        </div>
                    </div>
                </div>

                <div class="col-12 text-end mt-4">
                    <a href="index.php" class="btn btn-secondary px-3 me-2">Hủy</a>
                    <button type="submit" class="btn btn-success px-4 fw-semibold">
                        <i class="bi bi-plus-circle me-1"></i> Lưu và Thêm đặc sản
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>