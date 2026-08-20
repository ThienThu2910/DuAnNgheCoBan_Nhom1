<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';

$loi = [];

$tieuDe = '';
$slug = '';
$tomTat = '';
$noiDung = '';
$trangThai = 'nhap';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tieuDe = trim($_POST['tieu_de'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
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
    } else {
        $slug = taoSlug($slug);
    }

    if ($slug === '') {
        $loi[] = 'Đường dẫn bài viết không hợp lệ.';
    }

    if (!in_array($trangThai, ['nhap', 'xuat_ban'], true)) {
        $trangThai = 'nhap';
    }

    if ($slug !== '') {
        $kiemTraTrung = $pdo->prepare(
            'SELECT id FROM bai_viet WHERE slug = :slug OR tieu_de = :tieu_de LIMIT 1'
        );

        $kiemTraTrung->execute([
            'slug' => $slug,
            'tieu_de' => $tieuDe
        ]);

        if ($kiemTraTrung->fetch()) {
            $loi[] = 'Tiêu đề hoặc đường dẫn bài viết đã tồn tại.';
        }
    }

    $tenHinhAnh = null;
    $duongDanAnhMoi = null;
    $thuMucUpload = __DIR__ . '/../../assets/uploads/bai-viet/';

    if (!is_dir($thuMucUpload)) {
        mkdir($thuMucUpload, 0777, true);
    }

    // Upload ảnh đại diện chính
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
                $loi[] = 'Chỉ chấp nhận ảnh đại diện định dạng JPG, PNG hoặc WEBP.';
            } else {
                $tenHinhAnh = sprintf('%s-%s.%s', $slug, bin2hex(random_bytes(4)), $loaiAnhChoPhep[$mimeType]);
                $duongDanAnhMoi = $thuMucUpload . $tenHinhAnh;
            }
        }
    }

    if (empty($loi)) {
        try {
            if ($tenHinhAnh !== null && $duongDanAnhMoi !== null) {
                if (!move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $duongDanAnhMoi)) {
                    throw new RuntimeException('Không thể lưu hình ảnh đại diện bài viết.');
                }
            }

            $ngayDang = $trangThai === 'xuat_ban' ? date('Y-m-d H:i:s') : null;

            $stmt = $pdo->prepare(
                'INSERT INTO bai_viet (tieu_de, slug, tom_tat, noi_dung, hinh_anh, trang_thai, ngay_dang)
                 VALUES (:tieu_de, :slug, :tom_tat, :noi_dung, :hinh_anh, :trang_thai, :ngay_dang)'
            );

            $stmt->execute([
                'tieu_de'    => $tieuDe,
                'slug'       => $slug,
                'tom_tat'    => $tomTat !== '' ? $tomTat : null,
                'noi_dung'   => $noiDung,
                'hinh_anh'   => $tenHinhAnh,
                'trang_thai' => $trangThai,
                'ngay_dang'  => $ngayDang
            ]);

            $baiVietId = (int) $pdo->lastInsertId();

            // Upload nhiều ảnh phụ vào album
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
                        $newFileName = 'bv_gallery_' . $baiVietId . '_' . uniqid() . '.' . $ext;
                        $destination = $thuMucUpload . $newFileName;

                        if (move_uploaded_file($fileTmp, $destination)) {
                            $stmtInsertImage->execute([
                                ':bai_viet_id' => $baiVietId,
                                ':duong_dan'   => $newFileName,
                                ':mo_ta'       => pathinfo($fileName, PATHINFO_FILENAME),
                                ':thu_tu'      => $index + 1
                            ]);
                        }
                    }
                }
            }

            $_SESSION['success'] = 'Thêm bài viết thành công.';
            header('Location: index.php');
            exit;
        } catch (Throwable $e) {
            if ($duongDanAnhMoi !== null && is_file($duongDanAnhMoi)) {
                unlink($duongDanAnhMoi);
            }
            $loi[] = 'Không thể thêm bài viết: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm bài viết</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #imagePreview {
            display: none;
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 8px;
        }
        .ck-editor__editable_inline {
            min-height: 380px;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/DuAnNgheCoBan_Nhom1/admin/index.php">Quản trị đặc sản Cà Mau</a>
            <a href="/DuAnNgheCoBan_Nhom1/logout.php" class="btn btn-outline-light btn-sm">Đăng xuất</a>
        </div>
    </nav>

    <main class="container py-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                    <div>
                        <h1 class="h3 mb-1">Thêm bài viết</h1>
                        <p class="text-muted mb-0">Viết câu chuyện về văn hóa và đặc sản Cà Mau.</p>
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
                                <input type="text" id="tieu_de" name="tieu_de" class="form-control" value="<?= htmlspecialchars($tieuDe) ?>" required autofocus>
                            </div>

                            <div class="mb-3">
                                <label for="slug" class="form-label">Đường dẫn (Slug)</label>
                                <input type="text" id="slug" name="slug" class="form-control" value="<?= htmlspecialchars($slug) ?>" placeholder="Để trống để tự động tạo từ tiêu đề">
                                <div class="form-text">Ví dụ: nghe-gac-keo-ong-u-minh</div>
                            </div>

                            <div class="mb-3">
                                <label for="tom_tat" class="form-label">Tóm tắt ngắn</label>
                                <textarea id="tom_tat" name="tom_tat" class="form-control" rows="3" maxlength="1000"><?= htmlspecialchars($tomTat) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="noi_dung" class="form-label">Nội dung chi tiết <span class="text-danger">*</span></label>
                                <textarea id="noi_dung" name="noi_dung" class="form-control"><?= htmlspecialchars($noiDung) ?></textarea>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label for="hinh_anh" class="form-label fw-bold">Hình ảnh đại diện</label>
                                <input type="file" id="hinh_anh" name="hinh_anh" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                                <div class="form-text">JPG, PNG hoặc WEBP, tối đa 5 MB.</div>
                            </div>

                            <img id="imagePreview" src="" alt="Xem trước hình ảnh" class="mb-3">

                            <div class="mb-3">
                                <label for="hinh_anh_phu" class="form-label fw-bold">Album hình ảnh (chọn nhiều ảnh)</label>
                                <input type="file" id="hinh_anh_phu" name="hinh_anh_phu[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.webp">
                                <div class="form-text">Giữ Ctrl hoặc Shift để chọn nhiều ảnh.</div>
                            </div>

                            <div class="mb-4">
                                <label for="trang_thai" class="form-label fw-bold">Trạng thái bài viết</label>
                                <select id="trang_thai" name="trang_thai" class="form-select">
                                    <option value="nhap" <?= $trangThai === 'nhap' ? 'selected' : '' ?>>Lưu bản nháp</option>
                                    <option value="xuat_ban" <?= $trangThai === 'xuat_ban' ? 'selected' : '' ?>>Xuất bản</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-success w-100 py-2 fw-semibold">Lưu bài viết</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

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