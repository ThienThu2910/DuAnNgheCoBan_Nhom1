<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';

$loi = [];

$danhMucId = 0;
$tenDacSan = '';
$slug = '';
$moTaNgan = '';
$nguonGoc = '';
$moTaChiTiet = '';
$cachSuDung = '';
$cachBaoQuan = '';
$noiBat = 0;
$trangThai = 1;

/**
 * Chuyển chuỗi tiếng Việt thành slug.
 */
function taoSlug(string $chuoi): string
{
    $chuoi = mb_strtolower(trim($chuoi), 'UTF-8');

    $tim = [
        'á', 'à', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ',
        'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ',
        'é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ',
        'í', 'ì', 'ỉ', 'ĩ', 'ị',
        'ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ',
        'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ',
        'ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự',
        'ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ',
        'đ'
    ];

    $thay = [
        'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
        'a', 'a', 'a', 'a', 'a', 'a',
        'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
        'i', 'i', 'i', 'i', 'i',
        'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
        'o', 'o', 'o', 'o', 'o', 'o',
        'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
        'y', 'y', 'y', 'y', 'y',
        'd'
    ];

    $chuoi = str_replace($tim, $thay, $chuoi);
    $chuoi = preg_replace('/[^a-z0-9]+/', '-', $chuoi);

    return trim((string) $chuoi, '-');
}

/**
 * Lấy danh mục đang hoạt động.
 */
$stmtDanhMuc = $pdo->query(
    'SELECT id, ten_danh_muc
     FROM danh_muc
     WHERE trang_thai = 1
     ORDER BY thu_tu ASC, ten_danh_muc ASC'
);

$danhSachDanhMuc = $stmtDanhMuc->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $danhMucId = (int) ($_POST['danh_muc_id'] ?? 0);
    $tenDacSan = trim($_POST['ten_dac_san'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $moTaNgan = trim($_POST['mo_ta_ngan'] ?? '');
    $nguonGoc = trim($_POST['nguon_goc'] ?? '');
    $moTaChiTiet = trim($_POST['mo_ta_chi_tiet'] ?? '');
    $cachSuDung = trim($_POST['cach_su_dung'] ?? '');
    $cachBaoQuan = trim($_POST['cach_bao_quan'] ?? '');
    $noiBat = isset($_POST['noi_bat']) ? 1 : 0;
    $trangThai = isset($_POST['trang_thai']) ? 1 : 0;

    if ($danhMucId <= 0) {
        $loi[] = 'Vui lòng chọn danh mục.';
    }

    if ($tenDacSan === '') {
        $loi[] = 'Vui lòng nhập tên đặc sản.';
    }

    if ($slug === '') {
        $slug = taoSlug($tenDacSan);
    } else {
        $slug = taoSlug($slug);
    }

    if ($slug === '') {
        $loi[] = 'Đường dẫn đặc sản không hợp lệ.';
    }

    /*
     * Kiểm tra danh mục có tồn tại không.
     */
    if ($danhMucId > 0) {
        $kiemTraDanhMuc = $pdo->prepare(
            'SELECT id
             FROM danh_muc
             WHERE id = :id
             LIMIT 1'
        );

        $kiemTraDanhMuc->execute([
            'id' => $danhMucId
        ]);

        if (!$kiemTraDanhMuc->fetch()) {
            $loi[] = 'Danh mục đã chọn không tồn tại.';
        }
    }

    /*
     * Kiểm tra trùng tên hoặc slug.
     */
    if ($tenDacSan !== '' && $slug !== '') {
        $kiemTraTrung = $pdo->prepare(
            'SELECT id
             FROM dac_san
             WHERE ten_dac_san = :ten_dac_san
                OR slug = :slug
             LIMIT 1'
        );

        $kiemTraTrung->execute([
            'ten_dac_san' => $tenDacSan,
            'slug' => $slug
        ]);

        if ($kiemTraTrung->fetch()) {
            $loi[] = 'Tên đặc sản hoặc đường dẫn đã tồn tại.';
        }
    }

    /*
     * Kiểm tra hình ảnh.
     */
    $tenHinhAnh = null;
    $duongDanAnhMoi = null;

    if (
        isset($_FILES['hinh_anh'])
        && $_FILES['hinh_anh']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $file = $_FILES['hinh_anh'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $loi[] = 'Có lỗi xảy ra khi tải hình ảnh.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $loi[] = 'Hình ảnh không được vượt quá 5 MB.';
        } else {
            $thongTinAnh = getimagesize($file['tmp_name']);

            $loaiAnhChoPhep = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            $mimeType = $thongTinAnh['mime'] ?? '';

            if (!isset($loaiAnhChoPhep[$mimeType])) {
                $loi[] = 'Chỉ chấp nhận hình ảnh JPG, PNG hoặc WEBP.';
            } else {
                $phanMoRong = $loaiAnhChoPhep[$mimeType];

                $tenHinhAnh = sprintf(
                    '%s-%s.%s',
                    $slug,
                    bin2hex(random_bytes(4)),
                    $phanMoRong
                );

                $thuMucUpload = __DIR__
                    . '/../../assets/uploads/dac-san/';

                if (!is_dir($thuMucUpload)) {
                    mkdir($thuMucUpload, 0777, true);
                }

                $duongDanAnhMoi = $thuMucUpload . $tenHinhAnh;
            }
        }
    }

    if (empty($loi)) {
        try {
            /*
             * Di chuyển ảnh vào thư mục uploads.
             */
            if (
                $tenHinhAnh !== null
                && $duongDanAnhMoi !== null
            ) {
                if (
                    !move_uploaded_file(
                        $_FILES['hinh_anh']['tmp_name'],
                        $duongDanAnhMoi
                    )
                ) {
                    throw new RuntimeException(
                        'Không thể lưu hình ảnh lên máy chủ.'
                    );
                }
            }

            $stmt = $pdo->prepare(
                'INSERT INTO dac_san
                    (
                        danh_muc_id,
                        ten_dac_san,
                        slug,
                        mo_ta_ngan,
                        nguon_goc,
                        mo_ta_chi_tiet,
                        cach_su_dung,
                        cach_bao_quan,
                        hinh_anh,
                        noi_bat,
                        trang_thai
                    )
                 VALUES
                    (
                        :danh_muc_id,
                        :ten_dac_san,
                        :slug,
                        :mo_ta_ngan,
                        :nguon_goc,
                        :mo_ta_chi_tiet,
                        :cach_su_dung,
                        :cach_bao_quan,
                        :hinh_anh,
                        :noi_bat,
                        :trang_thai
                    )'
            );

            $stmt->execute([
                'danh_muc_id' => $danhMucId,
                'ten_dac_san' => $tenDacSan,
                'slug' => $slug,
                'mo_ta_ngan' => $moTaNgan !== ''
                    ? $moTaNgan
                    : null,
                'nguon_goc' => $nguonGoc !== ''
                    ? $nguonGoc
                    : null,
                'mo_ta_chi_tiet' => $moTaChiTiet !== ''
                    ? $moTaChiTiet
                    : null,
                'cach_su_dung' => $cachSuDung !== ''
                    ? $cachSuDung
                    : null,
                'cach_bao_quan' => $cachBaoQuan !== ''
                    ? $cachBaoQuan
                    : null,
                'hinh_anh' => $tenHinhAnh,
                'noi_bat' => $noiBat,
                'trang_thai' => $trangThai
            ]);

            $_SESSION['success'] = 'Thêm đặc sản thành công.';

            header('Location: index.php');
            exit;
        } catch (Throwable $e) {
            /*
             * Nếu lưu database thất bại thì xóa ảnh vừa tải.
             */
            if (
                $duongDanAnhMoi !== null
                && file_exists($duongDanAnhMoi)
            ) {
                unlink($duongDanAnhMoi);
            }

            $loi[] = 'Không thể thêm đặc sản: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Thêm đặc sản</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        #imagePreview {
            width: 220px;
            height: 150px;
            object-fit: cover;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            display: none;
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-success">
        <div class="container">
            <a
                class="navbar-brand fw-bold"
                href="/DuAnNgheCoBan_Nhom1/admin/index.php"
            >
                Quản trị đặc sản Cà Mau
            </a>

            <a
                href="/DuAnNgheCoBan_Nhom1/logout.php"
                class="btn btn-outline-light btn-sm"
            >
                Đăng xuất
            </a>
        </div>
    </nav>

    <main class="container py-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div
                    class="d-flex justify-content-between
                           align-items-center flex-wrap gap-2 mb-4"
                >
                    <div>
                        <h1 class="h3 mb-1">Thêm đặc sản</h1>

                        <p class="text-muted mb-0">
                            Nhập thông tin giới thiệu đặc sản Cà Mau.
                        </p>
                    </div>

                    <a
                        href="index.php"
                        class="btn btn-secondary"
                    >
                        Quay lại danh sách
                    </a>
                </div>

                <?php if (!empty($loi)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($loi as $noiDungLoi): ?>
                                <li>
                                    <?= htmlspecialchars($noiDungLoi) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (empty($danhSachDanhMuc)): ?>
                    <div class="alert alert-warning">
                        Chưa có danh mục đang hiển thị.

                        <a
                            href="/DuAnNgheCoBan_Nhom1/admin/danh-muc/create.php"
                            class="alert-link"
                        >
                            Thêm danh mục trước
                        </a>
                    </div>
                <?php endif; ?>

                <form
                    method="post"
                    enctype="multipart/form-data"
                >
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="mb-3">
                                <label
                                    for="ten_dac_san"
                                    class="form-label"
                                >
                                    Tên đặc sản
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="ten_dac_san"
                                    name="ten_dac_san"
                                    class="form-control"
                                    value="<?= htmlspecialchars($tenDacSan) ?>"
                                    required
                                    autofocus
                                >
                            </div>

                            <div class="mb-3">
                                <label for="slug" class="form-label">
                                    Đường dẫn
                                </label>

                                <input
                                    type="text"
                                    id="slug"
                                    name="slug"
                                    class="form-control"
                                    value="<?= htmlspecialchars($slug) ?>"
                                    placeholder="Để trống để tự tạo"
                                >

                                <div class="form-text">
                                    Ví dụ: cua-ca-mau
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="mo_ta_ngan"
                                    class="form-label"
                                >
                                    Mô tả ngắn
                                </label>

                                <textarea
                                    id="mo_ta_ngan"
                                    name="mo_ta_ngan"
                                    class="form-control"
                                    rows="3"
                                    maxlength="500"
                                ><?= htmlspecialchars($moTaNgan) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="nguon_goc"
                                    class="form-label"
                                >
                                    Nguồn gốc
                                </label>

                                <textarea
                                    id="nguon_goc"
                                    name="nguon_goc"
                                    class="form-control"
                                    rows="4"
                                ><?= htmlspecialchars($nguonGoc) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="mo_ta_chi_tiet"
                                    class="form-label"
                                >
                                    Nội dung giới thiệu chi tiết
                                </label>

                                <textarea
                                    id="mo_ta_chi_tiet"
                                    name="mo_ta_chi_tiet"
                                    class="form-control"
                                    rows="7"
                                ><?= htmlspecialchars($moTaChiTiet) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="cach_su_dung"
                                    class="form-label"
                                >
                                    Cách sử dụng
                                </label>

                                <textarea
                                    id="cach_su_dung"
                                    name="cach_su_dung"
                                    class="form-control"
                                    rows="4"
                                ><?= htmlspecialchars($cachSuDung) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="cach_bao_quan"
                                    class="form-label"
                                >
                                    Cách bảo quản
                                </label>

                                <textarea
                                    id="cach_bao_quan"
                                    name="cach_bao_quan"
                                    class="form-control"
                                    rows="4"
                                ><?= htmlspecialchars($cachBaoQuan) ?></textarea>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label
                                    for="danh_muc_id"
                                    class="form-label"
                                >
                                    Danh mục
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    id="danh_muc_id"
                                    name="danh_muc_id"
                                    class="form-select"
                                    required
                                >
                                    <option value="">
                                        Chọn danh mục
                                    </option>

                                    <?php foreach (
                                        $danhSachDanhMuc as $danhMuc
                                    ): ?>
                                        <option
                                            value="<?= (int) $danhMuc['id'] ?>"
                                            <?= $danhMucId === (int) $danhMuc['id']
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= htmlspecialchars(
                                                $danhMuc['ten_danh_muc']
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="hinh_anh"
                                    class="form-label"
                                >
                                    Hình ảnh đại diện
                                </label>

                                <input
                                    type="file"
                                    id="hinh_anh"
                                    name="hinh_anh"
                                    class="form-control"
                                    accept=".jpg,.jpeg,.png,.webp"
                                >

                                <div class="form-text">
                                    JPG, PNG hoặc WEBP, tối đa 5 MB.
                                </div>
                            </div>

                            <img
                                id="imagePreview"
                                src=""
                                alt="Xem trước hình ảnh"
                                class="mb-4"
                            >

                            <div class="form-check mb-3">
                                <input
                                    type="checkbox"
                                    id="noi_bat"
                                    name="noi_bat"
                                    class="form-check-input"
                                    <?= $noiBat === 1 ? 'checked' : '' ?>
                                >

                                <label
                                    for="noi_bat"
                                    class="form-check-label"
                                >
                                    Đặc sản nổi bật
                                </label>
                            </div>

                            <div class="form-check mb-4">
                                <input
                                    type="checkbox"
                                    id="trang_thai"
                                    name="trang_thai"
                                    class="form-check-input"
                                    <?= $trangThai === 1 ? 'checked' : '' ?>
                                >

                                <label
                                    for="trang_thai"
                                    class="form-check-label"
                                >
                                    Hiển thị trên website
                                </label>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-success w-100"
                                <?= empty($danhSachDanhMuc)
                                    ? 'disabled'
                                    : '' ?>
                            >
                                Lưu đặc sản
                            </button>
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