<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: index.php');
    exit;
}

// 1. Lấy thông tin đặc sản hiện tại
$stmt = $pdo->prepare('SELECT * FROM dac_san WHERE id = :id');
$stmt->execute(['id' => $id]);
$dacSan = $stmt->fetch();

if (!$dacSan) {
    header('Location: index.php');
    exit;
}

$thongBao = $_SESSION['success'] ?? '';
$loi = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// 2. Lấy danh mục
$stmtDanhMuc = $pdo->query('SELECT id, ten_danh_muc FROM danh_muc WHERE trang_thai = 1 ORDER BY thu_tu ASC');
$danhSachDanhMuc = $stmtDanhMuc->fetchAll();

// 3. Lấy danh sách ảnh phụ
$danhSachAnhPhu = [];
try {
    $stmtAnhPhu = $pdo->prepare('SELECT * FROM hinh_anh_dac_san WHERE dac_san_id = :id ORDER BY id ASC');
    $stmtAnhPhu->execute(['id' => $id]);
    $danhSachAnhPhu = $stmtAnhPhu->fetchAll();
} catch (\PDOException $e) {
    // Bỏ qua nếu bảng chưa tạo
}

// 4. Xử lý khi bấm nút Lưu thay đổi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDacSan = trim($_POST['ten_dac_san'] ?? '');
    $danhMucId = filter_input(INPUT_POST, 'danh_muc_id', FILTER_VALIDATE_INT);
    $moTaNgan = trim($_POST['mo_ta_ngan'] ?? '');
    $noiDungChiTiet = trim($_POST['noi_dung_chi_tiet'] ?? '');
    $noiBat = isset($_POST['noi_bat']) ? 1 : 0;
    $trangThai = isset($_POST['trang_thai']) ? 1 : 0;

    if ($tenDacSan === '') {
        $loi = 'Vui lòng nhập tên đặc sản.';
    } else {
        $hinhAnhChinh = $dacSan['hinh_anh'];
        $uploadDir = __DIR__ . '/../../assets/uploads/dac-san/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Tải ảnh đại diện chính mới (nếu có)
        if (!empty($_FILES['hinh_anh']['name']) && $_FILES['hinh_anh']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['hinh_anh']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array($ext, $allowed, true)) {
                $newFileName = 'main_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $uploadDir . $newFileName)) {
                    if ($hinhAnhChinh && file_exists($uploadDir . $hinhAnhChinh)) {
                        unlink($uploadDir . $hinhAnhChinh);
                    }
                    $hinhAnhChinh = $newFileName;
                }
            }
        }

        // Cập nhật bảng dac_san
        $updateFields = [
            'ten_dac_san = :ten',
            'danh_muc_id = :dm_id',
            'mo_ta_ngan = :mo_ta',
            'hinh_anh = :anh',
            'noi_bat = :noi_bat',
            'trang_thai = :trang_thai'
        ];
        $params = [
            'ten' => $tenDacSan,
            'dm_id' => $danhMucId,
            'mo_ta' => $moTaNgan,
            'anh' => $hinhAnhChinh,
            'noi_bat' => $noiBat,
            'trang_thai' => $trangThai,
            'id' => $id
        ];

        if (array_key_exists('noi_dung_chi_tiet', $dacSan)) {
            $updateFields[] = 'noi_dung_chi_tiet = :noi_dung';
            $params['noi_dung'] = $noiDungChiTiet;
        } elseif (array_key_exists('noi_dung', $dacSan)) {
            $updateFields[] = 'noi_dung = :noi_dung';
            $params['noi_dung'] = $noiDungChiTiet;
        }

        $sql = 'UPDATE dac_san SET ' . implode(', ', $updateFields) . ' WHERE id = :id';
        $stmtUpdate = $pdo->prepare($sql);
        $stmtUpdate->execute($params);

        // Tải lên và chèn nhiều ảnh phụ
        if (!empty($_FILES['anh_phu']['name'][0])) {
            $totalFiles = count($_FILES['anh_phu']['name']);
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            // Tự kiểm tra tên cột trong bảng hinh_anh_dac_san (hinh_anh, duong_dan_anh hoặc duong_dan)
            $colName = 'hinh_anh';
            try {
                $checkCols = $pdo->query('SHOW COLUMNS FROM hinh_anh_dac_san')->fetchAll(PDO::FETCH_COLUMN);
                if (in_array('duong_dan_anh', $checkCols, true)) {
                    $colName = 'duong_dan_anh';
                } elseif (in_array('duong_dan', $checkCols, true)) {
                    $colName = 'duong_dan';
                }
            } catch (\PDOException $e) {
                // Tạo bảng nếu chưa tồn tại
                $pdo->exec('CREATE TABLE IF NOT EXISTS hinh_anh_dac_san (id INT AUTO_INCREMENT PRIMARY KEY, dac_san_id INT, hinh_anh VARCHAR(255))');
            }

            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['anh_phu']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['anh_phu']['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed, true)) {
                        $subFileName = 'sub_' . time() . '_' . uniqid() . '.' . $ext;
                        if (move_uploaded_file($_FILES['anh_phu']['tmp_name'][$i], $uploadDir . $subFileName)) {
                            $stmtSub = $pdo->prepare("INSERT INTO hinh_anh_dac_san (dac_san_id, {$colName}) VALUES (:dac_san_id, :file_name)");
                            $stmtSub->execute([
                                'dac_san_id' => $id,
                                'file_name' => $subFileName
                            ]);
                        }
                    }
                }
            }
        }

        $_SESSION['success'] = 'Cập nhật thông tin đặc sản thành công!';
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
    <title>Chỉnh sửa đặc sản - Quản trị Cà Mau</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .sub-img-box { position: relative; width: 110px; height: 90px; border-radius: 8px; overflow: hidden; border: 1px solid #dee2e6; }
        .sub-img-box img { width: 100%; height: 100%; object-fit: cover; }
        .btn-delete-sub-img { position: absolute; top: 4px; right: 4px; border-radius: 50%; width: 26px; height: 26px; padding: 0; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body class="p-4">
<div class="container bg-white p-4 rounded shadow-sm" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h3 class="fw-bold mb-0">Chỉnh sửa đặc sản: <?= htmlspecialchars($dacSan['ten_dac_san']) ?></h3>
        <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Quay lại danh sách</a>
    </div>

    <?php if ($thongBao): ?><div class="alert alert-success"><?= htmlspecialchars($thongBao) ?></div><?php endif; ?>
    <?php if ($loi): ?><div class="alert alert-danger"><?= htmlspecialchars($loi) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label fw-semibold">Tên đặc sản <span class="text-danger">*</span></label>
            <input type="text" name="ten_dac_san" class="form-control" value="<?= htmlspecialchars($dacSan['ten_dac_san']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Danh mục</label>
            <select name="danh_muc_id" class="form-select">
                <option value="">-- Chọn danh mục --</option>
                <?php foreach ($danhSachDanhMuc as $dm): ?>
                    <option value="<?= $dm['id'] ?>" <?= (int)$dacSan['danh_muc_id'] === (int)$dm['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dm['ten_danh_muc']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Ảnh đại diện chính</label>
            <input type="file" name="hinh_anh" class="form-control mb-2" accept="image/*">
            <?php if (!empty($dacSan['hinh_anh'])): ?>
                <img src="/DuAnNgheCoBan_Nhom1/assets/uploads/dac-san/<?= htmlspecialchars($dacSan['hinh_anh']) ?>" class="rounded border" style="width: 120px; height: 90px; object-fit: cover;">
            <?php endif; ?>
        </div>

        <!-- MỤC QUẢN LÝ NHIỀU ẢNH PHỤ -->
        <div class="card p-3 mb-4 bg-light border">
            <h5 class="fw-bold mb-3"><i class="bi bi-images text-danger me-2"></i>Bộ sưu tập nhiều hình ảnh (Gallery)</h5>
            
            <label class="form-label fw-semibold">Tải thêm nhiều hình ảnh cùng lúc:</label>
            <input type="file" name="anh_phu[]" class="form-control mb-3" multiple accept="image/*">
            <small class="text-muted d-block mb-3">Giữ phím <code>Ctrl</code> khi chọn file để chọn tải lên nhiều ảnh cùng một lúc.</small>

            <?php if (!empty($danhSachAnhPhu)): ?>
                <label class="form-label fw-semibold">Các ảnh phụ hiện có (Bấm nút đỏ để xóa nếu tải nhầm):</label>
                <div class="d-flex gap-3 flex-wrap">
                    <?php foreach ($danhSachAnhPhu as $subImg): ?>
                        <?php $tenAnhSub = $subImg['hinh_anh'] ?? $subImg['duong_dan_anh'] ?? $subImg['duong_dan'] ?? ''; ?>
                        <?php if ($tenAnhSub): ?>
                            <div class="sub-img-box">
                                <img src="/DuAnNgheCoBan_Nhom1/assets/uploads/dac-san/<?= htmlspecialchars($tenAnhSub) ?>">
                                <button type="button" class="btn btn-danger btn-delete-sub-img" title="Xóa ảnh này" onclick="xacNhanXoaAnh(<?= $subImg['id'] ?>)">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Mô tả ngắn</label>
            <textarea name="mo_ta_ngan" class="form-control" rows="3"><?= htmlspecialchars($dacSan['mo_ta_ngan'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Nội dung chi tiết</label>
            <textarea name="noi_dung_chi_tiet" class="form-control" rows="6"><?= htmlspecialchars($dacSan['noi_dung_chi_tiet'] ?? $dacSan['noi_dung'] ?? '') ?></textarea>
        </div>

        <div class="d-flex gap-4 mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="noi_bat" value="1" id="noi_bat" <?= (int)($dacSan['noi_bat'] ?? 0) === 1 ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="noi_bat">Đặc sản nổi bật</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="trang_thai" value="1" id="trang_thai" <?= (int)($dacSan['trang_thai'] ?? 1) === 1 ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="trang_thai">Hiển thị ra ngoài trang web</label>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success px-4"><i class="bi bi-save me-1"></i> Lưu thay đổi</button>
            <a href="index.php" class="btn btn-secondary px-3">Hủy</a>
        </div>
    </form>
</div>

<!-- Form xóa ảnh -->
<form id="formXoaAnh" method="post" action="xoa-anh.php">
    <input type="hidden" name="anh_id" id="inputXoaAnhId" value="">
    <input type="hidden" name="dac_san_id" value="<?= $id ?>">
</form>

<script>
function xacNhanXoaAnh(anhId) {
    if (confirm('Bạn có chắc chắn muốn xóa hình ảnh này không?')) {
        document.getElementById('inputXoaAnhId').value = anhId;
        document.getElementById('formXoaAnh').submit();
    }
}
</script>
</body>
</html>