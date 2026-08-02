<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';

$loi = [];

$tenCoSo = '';
$slug = '';
$diaChi = '';
$soDienThoai = '';
$email = '';
$moTa = '';
$viDoRaw = '';
$kinhDoRaw = '';
$googleMapsUrl = '';
$trangThai = 1;

function taoSlug(string $chuoi): string
{
    $chuoi = mb_strtolower(trim($chuoi), 'UTF-8');

    $tim = [
        'á', 'à', 'ả', 'ã', 'ạ',
        'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ',
        'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ',
        'é', 'è', 'ẻ', 'ẽ', 'ẹ',
        'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ',
        'í', 'ì', 'ỉ', 'ĩ', 'ị',
        'ó', 'ò', 'ỏ', 'õ', 'ọ',
        'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ',
        'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ',
        'ú', 'ù', 'ủ', 'ũ', 'ụ',
        'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự',
        'ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ',
        'đ'
    ];

    $thay = [
        'a', 'a', 'a', 'a', 'a',
        'a', 'a', 'a', 'a', 'a', 'a',
        'a', 'a', 'a', 'a', 'a', 'a',
        'e', 'e', 'e', 'e', 'e',
        'e', 'e', 'e', 'e', 'e', 'e',
        'i', 'i', 'i', 'i', 'i',
        'o', 'o', 'o', 'o', 'o',
        'o', 'o', 'o', 'o', 'o', 'o',
        'o', 'o', 'o', 'o', 'o', 'o',
        'u', 'u', 'u', 'u', 'u',
        'u', 'u', 'u', 'u', 'u', 'u',
        'y', 'y', 'y', 'y', 'y',
        'd'
    ];

    $chuoi = str_replace($tim, $thay, $chuoi);
    $chuoi = preg_replace('/[^a-z0-9]+/', '-', $chuoi);

    return trim((string) $chuoi, '-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenCoSo = trim($_POST['ten_co_so'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $diaChi = trim($_POST['dia_chi'] ?? '');
    $soDienThoai = trim($_POST['so_dien_thoai'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $moTa = trim($_POST['mo_ta'] ?? '');
    $viDoRaw = trim($_POST['vi_do'] ?? '');
    $kinhDoRaw = trim($_POST['kinh_do'] ?? '');
    $googleMapsUrl = trim($_POST['google_maps_url'] ?? '');
    $trangThai = isset($_POST['trang_thai']) ? 1 : 0;

    if ($tenCoSo === '') {
        $loi[] = 'Vui lòng nhập tên cơ sở.';
    }

    if ($diaChi === '') {
        $loi[] = 'Vui lòng nhập địa chỉ.';
    }

    if ($slug === '') {
        $slug = taoSlug($tenCoSo);
    } else {
        $slug = taoSlug($slug);
    }

    if ($slug === '') {
        $loi[] = 'Đường dẫn cơ sở không hợp lệ.';
    }

    if (
        $email !== ''
        && !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $loi[] = 'Địa chỉ email không hợp lệ.';
    }

    if (
        $googleMapsUrl !== ''
        && !filter_var($googleMapsUrl, FILTER_VALIDATE_URL)
    ) {
        $loi[] = 'Đường dẫn Google Maps không hợp lệ.';
    }

    $viDo = null;
    $kinhDo = null;

    if ($viDoRaw !== '') {
        $viDo = filter_var($viDoRaw, FILTER_VALIDATE_FLOAT);

        if ($viDo === false || $viDo < -90 || $viDo > 90) {
            $loi[] = 'Vĩ độ phải là số từ -90 đến 90.';
        }
    }

    if ($kinhDoRaw !== '') {
        $kinhDo = filter_var($kinhDoRaw, FILTER_VALIDATE_FLOAT);

        if ($kinhDo === false || $kinhDo < -180 || $kinhDo > 180) {
            $loi[] = 'Kinh độ phải là số từ -180 đến 180.';
        }
    }

    $kiemTraTrung = $pdo->prepare(
        'SELECT id
         FROM co_so_san_xuat
         WHERE ten_co_so = :ten_co_so
            OR slug = :slug
         LIMIT 1'
    );

    $kiemTraTrung->execute([
        'ten_co_so' => $tenCoSo,
        'slug' => $slug
    ]);

    if ($kiemTraTrung->fetch()) {
        $loi[] = 'Tên cơ sở hoặc đường dẫn đã tồn tại.';
    }

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
            $thongTinAnh = @getimagesize($file['tmp_name']);

            $loaiAnhChoPhep = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            $mimeType = $thongTinAnh['mime'] ?? '';

            if (!isset($loaiAnhChoPhep[$mimeType])) {
                $loi[] = 'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.';
            } else {
                $phanMoRong = $loaiAnhChoPhep[$mimeType];

                $tenHinhAnh = sprintf(
                    '%s-%s.%s',
                    $slug,
                    bin2hex(random_bytes(4)),
                    $phanMoRong
                );

                $thuMucUpload =
                    __DIR__ . '/../../assets/uploads/co-so/';

                if (!is_dir($thuMucUpload)) {
                    mkdir($thuMucUpload, 0777, true);
                }

                $duongDanAnhMoi =
                    $thuMucUpload . $tenHinhAnh;
            }
        }
    }

    if (empty($loi)) {
        try {
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
                        'Không thể lưu hình ảnh.'
                    );
                }
            }

            $stmt = $pdo->prepare(
                'INSERT INTO co_so_san_xuat
                    (
                        ten_co_so,
                        slug,
                        dia_chi,
                        so_dien_thoai,
                        email,
                        mo_ta,
                        hinh_anh,
                        vi_do,
                        kinh_do,
                        google_maps_url,
                        trang_thai
                    )
                 VALUES
                    (
                        :ten_co_so,
                        :slug,
                        :dia_chi,
                        :so_dien_thoai,
                        :email,
                        :mo_ta,
                        :hinh_anh,
                        :vi_do,
                        :kinh_do,
                        :google_maps_url,
                        :trang_thai
                    )'
            );

            $stmt->execute([
                'ten_co_so' => $tenCoSo,
                'slug' => $slug,
                'dia_chi' => $diaChi,
                'so_dien_thoai' => $soDienThoai !== ''
                    ? $soDienThoai
                    : null,
                'email' => $email !== ''
                    ? $email
                    : null,
                'mo_ta' => $moTa !== ''
                    ? $moTa
                    : null,
                'hinh_anh' => $tenHinhAnh,
                'vi_do' => $viDo,
                'kinh_do' => $kinhDo,
                'google_maps_url' => $googleMapsUrl !== ''
                    ? $googleMapsUrl
                    : null,
                'trang_thai' => $trangThai
            ]);

            $_SESSION['success'] =
                'Thêm cơ sở sản xuất thành công.';

            header('Location: index.php');
            exit;
        } catch (Throwable $e) {
            if (
                $duongDanAnhMoi !== null
                && is_file($duongDanAnhMoi)
            ) {
                unlink($duongDanAnhMoi);
            }

            $loi[] = 'Không thể thêm cơ sở: '
                . $e->getMessage();
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

    <title>Thêm cơ sở sản xuất</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        #imagePreview {
            display: none;
            width: 100%;
            max-width: 280px;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-success">
        <div class="container">
            <a
                class="navbar-brand fw-bold"
                href="/dac-san-ca-mau/admin/index.php"
            >
                Quản trị đặc sản Cà Mau
            </a>

            <a
                href="/dac-san-ca-mau/logout.php"
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
                        <h1 class="h3 mb-1">
                            Thêm cơ sở sản xuất
                        </h1>

                        <p class="text-muted mb-0">
                            Nhập thông tin địa điểm và vị trí Google Maps.
                        </p>
                    </div>

                    <a href="index.php" class="btn btn-secondary">
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

                <form
                    method="post"
                    enctype="multipart/form-data"
                >
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="mb-3">
                                <label
                                    for="ten_co_so"
                                    class="form-label"
                                >
                                    Tên cơ sở
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="ten_co_so"
                                    name="ten_co_so"
                                    class="form-control"
                                    value="<?= htmlspecialchars($tenCoSo) ?>"
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
                            </div>

                            <div class="mb-3">
                                <label
                                    for="dia_chi"
                                    class="form-label"
                                >
                                    Địa chỉ
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="dia_chi"
                                    name="dia_chi"
                                    class="form-control"
                                    value="<?= htmlspecialchars($diaChi) ?>"
                                    required
                                >
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label
                                        for="so_dien_thoai"
                                        class="form-label"
                                    >
                                        Số điện thoại
                                    </label>

                                    <input
                                        type="text"
                                        id="so_dien_thoai"
                                        name="so_dien_thoai"
                                        class="form-control"
                                        value="<?= htmlspecialchars(
                                            $soDienThoai
                                        ) ?>"
                                    >
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label
                                        for="email"
                                        class="form-label"
                                    >
                                        Email
                                    </label>

                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        class="form-control"
                                        value="<?= htmlspecialchars($email) ?>"
                                    >
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="mo_ta" class="form-label">
                                    Mô tả
                                </label>

                                <textarea
                                    id="mo_ta"
                                    name="mo_ta"
                                    class="form-control"
                                    rows="5"
                                ><?= htmlspecialchars($moTa) ?></textarea>
                            </div>

                            <h2 class="h5 text-success mt-4">
                                Thông tin vị trí
                            </h2>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label
                                        for="vi_do"
                                        class="form-label"
                                    >
                                        Vĩ độ
                                    </label>

                                    <input
                                        type="number"
                                        step="any"
                                        id="vi_do"
                                        name="vi_do"
                                        class="form-control"
                                        value="<?= htmlspecialchars($viDoRaw) ?>"
                                        placeholder="Ví dụ: 9.1765000"
                                    >
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label
                                        for="kinh_do"
                                        class="form-label"
                                    >
                                        Kinh độ
                                    </label>

                                    <input
                                        type="number"
                                        step="any"
                                        id="kinh_do"
                                        name="kinh_do"
                                        class="form-control"
                                        value="<?= htmlspecialchars(
                                            $kinhDoRaw
                                        ) ?>"
                                        placeholder="Ví dụ: 105.1524000"
                                    >
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="google_maps_url"
                                    class="form-label"
                                >
                                    Đường dẫn Google Maps
                                </label>

                                <input
                                    type="url"
                                    id="google_maps_url"
                                    name="google_maps_url"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $googleMapsUrl
                                    ) ?>"
                                    placeholder="https://maps.google.com/..."
                                >

                                <div class="form-text">
                                    Có thể nhập tọa độ hoặc đường dẫn
                                    Google Maps. Không bắt buộc nhập cả hai.
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label
                                    for="hinh_anh"
                                    class="form-label"
                                >
                                    Hình ảnh cơ sở
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

                            <div class="form-check mb-4">
                                <input
                                    type="checkbox"
                                    id="trang_thai"
                                    name="trang_thai"
                                    class="form-check-input"
                                    <?= $trangThai === 1
                                        ? 'checked'
                                        : '' ?>
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
                            >
                                Lưu cơ sở
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