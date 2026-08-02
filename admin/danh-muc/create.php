<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';

$loi = [];

$tenDanhMuc = '';
$slug = '';
$moTa = '';
$thuTu = 0;
$trangThai = 1;

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDanhMuc = trim($_POST['ten_danh_muc'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $moTa = trim($_POST['mo_ta'] ?? '');
    $thuTu = (int) ($_POST['thu_tu'] ?? 0);
    $trangThai = isset($_POST['trang_thai']) ? 1 : 0;

    if ($tenDanhMuc === '') {
        $loi[] = 'Vui lòng nhập tên danh mục.';
    }

    if ($slug === '') {
        $slug = taoSlug($tenDanhMuc);
    } else {
        $slug = taoSlug($slug);
    }

    if ($slug === '') {
        $loi[] = 'Đường dẫn danh mục không hợp lệ.';
    }

    $kiemTra = $pdo->prepare(
        'SELECT id
         FROM danh_muc
         WHERE ten_danh_muc = :ten_danh_muc
            OR slug = :slug
         LIMIT 1'
    );

    $kiemTra->execute([
        'ten_danh_muc' => $tenDanhMuc,
        'slug' => $slug
    ]);

    if ($kiemTra->fetch()) {
        $loi[] = 'Tên danh mục hoặc đường dẫn đã tồn tại.';
    }

    if (empty($loi)) {
        $stmt = $pdo->prepare(
            'INSERT INTO danh_muc
                (
                    ten_danh_muc,
                    slug,
                    mo_ta,
                    thu_tu,
                    trang_thai
                )
             VALUES
                (
                    :ten_danh_muc,
                    :slug,
                    :mo_ta,
                    :thu_tu,
                    :trang_thai
                )'
        );

        $stmt->execute([
            'ten_danh_muc' => $tenDanhMuc,
            'slug' => $slug,
            'mo_ta' => $moTa !== '' ? $moTa : null,
            'thu_tu' => $thuTu,
            'trang_thai' => $trangThai
        ]);

        $_SESSION['success'] = 'Thêm danh mục thành công.';

        header('Location: index.php');
        exit;
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

    <title>Thêm danh mục</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h3 mb-4">
                            Thêm danh mục
                        </h1>

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

                        <form method="post">
                            <div class="mb-3">
                                <label
                                    for="ten_danh_muc"
                                    class="form-label"
                                >
                                    Tên danh mục
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="ten_danh_muc"
                                    name="ten_danh_muc"
                                    class="form-control"
                                    value="<?= htmlspecialchars($tenDanhMuc) ?>"
                                    required
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
                                    Ví dụ: thuy-hai-san
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
                                    rows="4"
                                ><?= htmlspecialchars($moTa) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="thu_tu" class="form-label">
                                    Thứ tự hiển thị
                                </label>

                                <input
                                    type="number"
                                    id="thu_tu"
                                    name="thu_tu"
                                    class="form-control"
                                    value="<?= $thuTu ?>"
                                    min="0"
                                >
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
                                    Hiển thị danh mục
                                </label>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-success"
                            >
                                Lưu danh mục
                            </button>

                            <a
                                href="index.php"
                                class="btn btn-secondary"
                            >
                                Hủy
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>