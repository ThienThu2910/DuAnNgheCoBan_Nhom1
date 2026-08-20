<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';

function taoSlug(string $chuoi): string
{
    $chuoi = mb_strtolower(trim($chuoi), 'UTF-8');

    $tim = [
        'á', 'à', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ',
        'é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ',
        'í', 'ì', 'ỉ', 'ĩ', 'ị',
        'ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ',
        'ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự',
        'ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ',
        'đ'
    ];

    $thay = [
        'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
        'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
        'i', 'i', 'i', 'i', 'i',
        'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
        'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
        'y', 'y', 'y', 'y', 'y',
        'd'
    ];

    $chuoi = str_replace($tim, $thay, $chuoi);
    $chuoi = preg_replace('/[^a-z0-9]+/', '-', $chuoi);

    return trim((string) $chuoi, '-');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['error'] = 'Mã bài viết không hợp lệ.';
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM bai_viet WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$baiViet = $stmt->fetch();

if (!$baiViet) {
    $_SESSION['error'] = 'Không tìm thấy bài viết.';
    header('Location: index.php');
    exit;
}

$loi = [];

$tieuDe = $baiViet['tieu_de'];
$slug = $baiViet['slug'];
$tomTat = $baiViet['tom_tat'] ?? '';
$noiDung = $baiViet['noi_dung'];
$trangThai = $baiViet['trang_thai'];
$hinhAnhCu = $baiViet['hinh_anh'] ?? null;
$ngayDangCu = $baiViet['ngay_dang'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tieuDe = trim($_POST['tieu_de'] ?? '');
    $slug = taoSlug($_POST['slug'] ?? '');
    $tomTat = trim($_POST['tom_tat'] ?? '');
    $noiDung = trim($_POST['noi_dung'] ?? '');
    $trangThai = $_POST['trang_thai'] ?? 'nhap';

    if ($tieuDe === '') {
        $loi[] = 'Vui lòng nhập tiêu đề bài viết.';
    }

    if ($noiDung === '' || $noiDung === '<p>&nbsp;</p>') {
        $loi[] = 'Vui lòng nhập nội dung bài viết.';
    }

    if ($slug === '') {
        $slug = taoSlug($tieuDe);
    }

    if ($slug === '') {
        $loi[] = 'Đường dẫn bài viết không hợp lệ.';
    }

    if (!in_array($trangThai, ['nhap', 'xuat_ban'], true)) {
        $trangThai = 'nhap';
    }

    $kiemTraTrung = $pdo->prepare(
        'SELECT id FROM bai_viet WHERE (tieu_de = :tieu_de OR slug = :slug) AND id <> :id LIMIT 1'
    );

    $kiemTraTrung->execute([
        'tieu_de' => $tieuDe,
        'slug'    => $slug,
        'id'      => $id
    ]);

    if ($kiemTraTrung->fetch()) {
        $loi[] = 'Tiêu đề hoặc đường dẫn bài viết đã tồn tại.';
    }

    $tenHinhAnhMoi = null;
    $duongDanAnhMoi = null;
    $thuMucUpload = __DIR__ . '/../../assets/uploads/bai-viet/';

    if (!is_dir($thuMucUpload)) {
        mkdir($thuMucUpload, 0777, true);
    }

    // Upload cập nhật ảnh đại diện chính
    if (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['hinh_anh'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $loi[] = 'Có lỗi xảy ra khi tải hình ảnh đại diện.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $loi[] = 'Hình ảnh đại diện không được vượt quá 5 MB.';
        } else {
            $thongTinAnh = @getimagesize($file['tmp_name']);
            $loaiAnhChoPhep = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            $mimeType = $thongTinAnh['mime'] ?? '';

            if (!isset($loaiAnhChoPhep[$mimeType])) {
                $loi[] = 'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.';
            } else {
                $tenHinhAnhMoi = sprintf('%s-%s.%s', $slug, bin2hex(random_bytes(4)), $loaiAnhChoPhep[$mimeType]);
                $duongDanAnhMoi = $thuMucUpload . $tenHinhAnhMoi;
            }
        }
    }

    if (empty($loi)) {
        try {
            if ($tenHinhAnhMoi !== null && $duongDanAnhMoi !== null) {
                if (!move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $duongDanAnhMoi)) {
                    throw new RuntimeException('Không thể lưu hình ảnh đại diện mới.');
                }
            }

            $hinhAnhLuu = $tenHinhAnhMoi ?? $hinhAnhCu;
            $ngayDang = ($trangThai === 'xuat_ban') ? ($ngayDangCu ?: date('Y-m-d H:i:s')) : null;

            $capNhat = $pdo->prepare(
                'UPDATE bai_viet
                 SET tieu_de = :tieu_de,
                     slug = :slug,
                     tom_tat = :tom_tat,
                     noi_dung = :noi_dung,
                     hinh_anh = :hinh_anh,
                     trang_thai = :trang_thai,
                     ngay_dang = :ngay_dang
                 WHERE id = :id'
            );

            $capNhat->execute([
                'tieu_de'    => $tieuDe,
                'slug'       => $slug,
                'tom_tat'    => $tomTat !== '' ? $tomTat : null,
                'noi_dung'   => $noiDung,
                'hinh_anh'   => $hinhAnhLuu,
                'trang_thai' => $trangThai,
                'ngay_dang'  => $ngayDang,
                'id'         => $id
            ]);

            // Xóa ảnh đại diện cũ trên ổ đĩa nếu đã upload ảnh mới
            if ($tenHinhAnhMoi !== null && !empty($hinhAnhCu)) {
                $duongDanAnhCu = $thuMucUpload . basename($hinhAnhCu);
                if (is_file($duongDanAnhCu)) {
                    unlink($duongDanAnhCu);
                }
            }

            // Upload thêm nhiều ảnh phụ mới vào album
            if (isset($_FILES['hinh_anh_phu']) && !empty($_FILES['hinh_anh_phu']['name'][0])) {
                $files = $_FILES['hinh_anh_phu'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                
                $stmtInsertImage = $pdo->prepare("
                    INSERT INTO hinh_anh_bai_viet (bai_viet_id, duong_dan, mo_ta, thu_tu) 
                    VALUES (:bai_viet_id, :duong_dan, :mo_ta, :thu_tu)
                ");

                foreach ($files['name'] as $index => $fileName) {
                    $fileTmp   = $files['tmp_name'][$index];
                    $fileType  = $files['type'][$index];
                    $fileError = $files['error'][$index];

                    if ($fileError === UPLOAD_ERR_OK && in_array($fileType, $allowedTypes)) {
                        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                        $newFileName = 'bv_gallery_' . $id . '_' . uniqid() . '.' . $ext;
                        $destination = $thuMucUpload . $newFileName;

                        if (move_uploaded_file($fileTmp, $destination)) {
                            $stmtInsertImage->execute([
                                ':bai_viet_id' => $id,
                                ':duong_dan'   => $newFileName,
                                ':mo_ta'       => pathinfo($fileName, PATHINFO_FILENAME),
                                ':thu_tu'      => $index + 1
                            ]);
                        }
                    }
                }
            }

            $_SESSION['success'] = 'Cập nhật bài viết thành công.';
            header('Location: index.php');
            exit;
        } catch (Throwable $e) {
            if ($duongDanAnhMoi !== null && is_file($duongDanAnhMoi)) {
                unlink($duongDanAnhMoi);
            }
            $loi[] = 'Không thể cập nhật bài viết: ' . $e->getMessage();
        }
    }
}

// Lấy danh sách ảnh trong album
$stmtImg = $pdo->prepare("SELECT id, duong_dan FROM hinh_anh_bai_viet WHERE bai_viet_id = :id ORDER BY thu_tu ASC, id ASC");
$stmtImg->execute([':id' => $id]);
$listAnh = $stmtImg->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa bài viết</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .current-image,
        #imagePreview {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 8px;
        }
        #imagePreview {
            display: none;
        }
        .ck-editor__editable_inline {
            min-height: 380px;
        }
        .album-thumb-container {
            position: relative;
        }
        .album-thumb-img {
            width: 100%;
            height: 90px;
            object-fit: cover;
            border-radius: 6px;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-success">
        <div class="container">
            <a href="/DuAnNgheCoBan_Nhom1/admin/index.php" class="navbar-brand fw-bold">Quản trị đặc sản Cà Mau</a>
            <a href="/DuAnNgheCoBan_Nhom1/logout.php" class="btn btn-outline-light btn-sm">Đăng xuất</a>
        </div>
    </nav>

    <main class="container py-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                    <div>
                        <h1 class="h3 mb-1">Sửa bài viết</h1>
                        <p class="text-muted mb-0">Cập nhật nội dung câu chuyện đặc sản.</p>
                    </div>
                    <a href="index.php" class="btn btn-secondary">Quay lại danh sách</a>
                </div>

                <?php if (!empty($loi)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($loi as $noiDungLoi): ?>
                                <li><?= htmlspecialchars($noiDungLoi) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="mb-3">
                                <label for="tieu_de" class="form-label">Tiêu đề bài viết <span class="text-danger">*</span></label>
                                <input type="text" id="tieu_de" name="tieu_de" class="form-control" value="<?= htmlspecialchars($tieuDe) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="slug" class="form-label">Đường dẫn (Slug)</label>
                                <input type="text" id="slug" name="slug" class="form-control" value="<?= htmlspecialchars($slug) ?>">
                            </div>

                            <div class="mb-3">
                                <label for="tom_tat" class="form-label">Tóm tắt ngắn</label>
                                <textarea id="tom_tat" name="tom_tat" class="form-control" rows="3"><?= htmlspecialchars($tomTat) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="noi_dung" class="form-label">Nội dung bài viết <span class="text-danger">*</span></label>
                                <textarea id="noi_dung" name="noi_dung" class="form-control"><?= htmlspecialchars($noiDung) ?></textarea>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Hình ảnh đại diện hiện tại</label>
                                <?php if (!empty($hinhAnhCu)): ?>
                                    <img src="/DuAnNgheCoBan_Nhom1/assets/uploads/bai-viet/<?= htmlspecialchars($hinhAnhCu) ?>" alt="<?= htmlspecialchars($tieuDe) ?>" class="current-image mb-2">
                                <?php else: ?>
                                    <div class="alert alert-light text-center py-2 mb-2">Chưa có ảnh đại diện</div>
                                <?php endif; ?>
                                <label for="hinh_anh" class="form-label small text-muted">Đổi ảnh đại diện mới:</label>
                                <input type="file" id="hinh_anh" name="hinh_anh" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            </div>

                            <img id="imagePreview" src="" alt="Xem trước ảnh mới" class="mb-3">

                            <!-- Khối hiển thị & tải album ảnh phụ -->
                            <div class="mb-3 p-3 bg-white border rounded">
                                <label class="form-label fw-bold d-block">Album hình ảnh</label>
                                <div class="row g-2 mb-2">
                                    <?php if (!empty($listAnh)): ?>
                                        <?php foreach ($listAnh as $img): ?>
                                            <div class="col-6 col-md-4 album-thumb-container">
                                                <img src="/DuAnNgheCoBan_Nhom1/assets/uploads/bai-viet/<?= htmlspecialchars($img['duong_dan']) ?>" class="album-thumb-img border">
                                                <button type="submit" form="form-xoa-<?= $img['id'] ?>" class="btn btn-danger btn-sm w-100 mt-1 py-0" style="font-size: 12px;" onclick="return confirm('Bạn có chắc muốn xóa ảnh này khỏi album?')">Xóa</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-12 text-muted small">Chưa có ảnh phụ nào trong album.</div>
                                    <?php endif; ?>
                                </div>

                                <label for="hinh_anh_phu" class="form-label small text-muted mt-2">Thêm ảnh mới vào Album:</label>
                                <input type="file" id="hinh_anh_phu" name="hinh_anh_phu[]" class="form-control form-control-sm" multiple accept=".jpg,.jpeg,.png,.webp">
                            </div>

                            <div class="mb-4">
                                <label for="trang_thai" class="form-label fw-bold">Trạng thái</label>
                                <select id="trang_thai" name="trang_thai" class="form-select">
                                    <option value="nhap" <?= $trangThai === 'nhap' ? 'selected' : '' ?>>Bản nháp</option>
                                    <option value="xuat_ban" <?= $trangThai === 'xuat_ban' ? 'selected' : '' ?>>Xuất bản</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-success w-100 py-2 fw-semibold">Cập nhật bài viết</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Các form ẩn xử lý xóa ảnh phụ -->
    <?php if (!empty($listAnh)): ?>
        <?php foreach ($listAnh as $img): ?>
            <form id="form-xoa-<?= $img['id'] ?>" action="xoa-anh.php" method="POST" style="display:none;">
                <input type="hidden" name="anh_id" value="<?= $img['id'] ?>">
                <input type="hidden" name="bai_viet_id" value="<?= $id ?>">
            </form>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- CKEditor 5 Classic -->
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#noi_dung'), {
                toolbar: [
                    'heading', '|', 
                    'bold', 'italic', 'underline', 'link', '|', 
                    'bulletedList', 'numberedList', 'blockQuote', '|', 
                    'insertTable', 'undo', 'redo'
                ]
            })
            .catch(error => {
                console.error(error);
            });

        const imageInput = document.getElementById('hinh_anh');
        const imagePreview = document.getElementById('imagePreview');

        imageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) {
                imagePreview.src = '';
                imagePreview.style.display = 'none';
                return;
            }
            imagePreview.src = URL.createObjectURL(file);
            imagePreview.style.display = 'block';
        });
    </script>
</body>
</html>