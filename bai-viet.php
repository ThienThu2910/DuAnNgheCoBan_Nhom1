<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$pageTitle = 'Câu chuyện đặc sản Cà Mau';
$currentPage = 'bai-viet';

$tuKhoa = trim($_GET['q'] ?? '');

$page = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT
);

if (!$page || $page < 1) {
    $page = 1;
}

$limit = 6;
$offset = ($page - 1) * $limit;

$where = " WHERE trang_thai = 'xuat_ban' ";
$params = [];

if ($tuKhoa !== '') {
    $where .= '
        AND (
            tieu_de LIKE :tu_khoa
            OR tom_tat LIKE :tu_khoa
        )
    ';

    $params['tu_khoa'] = '%' . $tuKhoa . '%';
}

$stmtDem = $pdo->prepare(
    'SELECT COUNT(*) AS tong
     FROM bai_viet'
    . $where
);

$stmtDem->execute($params);

$tongBaiViet = (int) $stmtDem->fetch()['tong'];
$tongTrang = max(1, (int) ceil($tongBaiViet / $limit));

if ($page > $tongTrang) {
    $page = $tongTrang;
    $offset = ($page - 1) * $limit;
}

$stmt = $pdo->prepare(
    'SELECT
        id,
        tieu_de,
        tom_tat,
        hinh_anh,
        ngay_dang
     FROM bai_viet'
    . $where
    . ' ORDER BY ngay_dang DESC, id DESC
        LIMIT :limit OFFSET :offset'
);

foreach ($params as $ten => $giaTri) {
    $stmt->bindValue(
        ':' . $ten,
        $giaTri,
        PDO::PARAM_STR
    );
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$danhSachBaiViet = $stmt->fetchAll();

function taoDuongDanTrang(
    int $page,
    string $tuKhoa
): string {
    $params = ['page' => $page];

    if ($tuKhoa !== '') {
        $params['q'] = $tuKhoa;
    }

    return '?' . http_build_query($params);
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
    .article-banner {
        padding: 70px 0;
        color: #ffffff;
        text-align: center;
        background:
            linear-gradient(
                rgba(20, 77, 54, 0.84),
                rgba(20, 77, 54, 0.84)
            ),
            url("/DuAnNgheCoBan_Nhom1/assets/images/banner-ca-mau.jpg")
            center / cover no-repeat;
    }

    .article-card {
        overflow: hidden;
        border: 0;
        border-radius: 14px;
        transition:
            transform 0.25s ease,
            box-shadow 0.25s ease;
    }

    .article-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 14px 30px rgba(0, 0, 0, 0.12);
    }

    .article-image,
    .article-no-image {
        width: 100%;
        height: 230px;
        object-fit: cover;
    }

    .article-no-image {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        background-color: #e9ecef;
    }

    .article-summary {
        display: -webkit-box;
        min-height: 72px;
        overflow: hidden;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
    }
</style>

<section class="article-banner">
    <div class="container">
        <h1 class="fw-bold">Câu chuyện đặc sản</h1>

        <p class="lead mb-0">
            Tìm hiểu văn hóa, nghề truyền thống và con người Cà Mau.
        </p>
    </div>
</section>

<main class="container py-5">
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-4">
            <form method="get" class="row g-3">
                <div class="col-md-10">
                    <label for="q" class="form-label">
                        Tìm kiếm bài viết
                    </label>

                    <input
                        type="text"
                        id="q"
                        name="q"
                        class="form-control"
                        placeholder="Nhập tiêu đề hoặc nội dung cần tìm..."
                        value="<?= htmlspecialchars($tuKhoa) ?>"
                    >
                </div>

                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button
                        type="submit"
                        class="btn btn-success flex-grow-1"
                    >
                        Tìm
                    </button>

                    <a
                        href="/DuAnNgheCoBan_Nhom1/bai-viet.php"
                        class="btn btn-secondary"
                    >
                        Xóa
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="mb-4">
        <h2 class="section-title mb-1">
            Bài viết mới nhất
        </h2>

        <p class="text-muted mb-0">
            Tìm thấy <?= $tongBaiViet ?> bài viết.
        </p>
    </div>

    <?php if (empty($danhSachBaiViet)): ?>
        <div class="alert alert-warning text-center py-4">
            Không tìm thấy bài viết phù hợp.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($danhSachBaiViet as $baiViet): ?>
                <div class="col-md-6 col-lg-4">
                    <article class="card article-card h-100 shadow-sm">
                        <?php if (!empty($baiViet['hinh_anh'])): ?>
                            <img
                                src="/DuAnNgheCoBan_Nhom1/assets/uploads/bai-viet/<?= htmlspecialchars(
                                    $baiViet['hinh_anh']
                                ) ?>"
                                alt="<?= htmlspecialchars(
                                    $baiViet['tieu_de']
                                ) ?>"
                                class="article-image"
                            >
                        <?php else: ?>
                            <div class="article-no-image">
                                Chưa có hình ảnh
                            </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">
                            <?php if (!empty($baiViet['ngay_dang'])): ?>
                                <p class="small text-muted mb-2">
                                    <?= date(
                                        'd/m/Y',
                                        strtotime($baiViet['ngay_dang'])
                                    ) ?>
                                </p>
                            <?php endif; ?>

                            <h3 class="h5 fw-bold">
                                <?= htmlspecialchars(
                                    $baiViet['tieu_de']
                                ) ?>
                            </h3>

                            <p class="text-muted article-summary">
                                <?= htmlspecialchars(
                                    $baiViet['tom_tat']
                                        ?: 'Nội dung bài viết đang được cập nhật.'
                                ) ?>
                            </p>

                            <a
                                href="/DuAnNgheCoBan_Nhom1/chi-tiet-bai-viet.php?id=<?= (int) $baiViet['id'] ?>"
                                class="btn btn-outline-success mt-auto"
                            >
                                Đọc bài viết
                            </a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($tongTrang > 1): ?>
            <nav class="mt-5">
                <ul class="pagination justify-content-center">
                    <li
                        class="page-item <?= $page <= 1
                            ? 'disabled'
                            : '' ?>"
                    >
                        <a
                            class="page-link"
                            href="<?= htmlspecialchars(
                                taoDuongDanTrang(
                                    max(1, $page - 1),
                                    $tuKhoa
                                )
                            ) ?>"
                        >
                            Trước
                        </a>
                    </li>

                    <?php for ($i = 1; $i <= $tongTrang; $i++): ?>
                        <li
                            class="page-item <?= $i === $page
                                ? 'active'
                                : '' ?>"
                        >
                            <a
                                class="page-link"
                                href="<?= htmlspecialchars(
                                    taoDuongDanTrang($i, $tuKhoa)
                                ) ?>"
                            >
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li
                        class="page-item <?= $page >= $tongTrang
                            ? 'disabled'
                            : '' ?>"
                    >
                        <a
                            class="page-link"
                            href="<?= htmlspecialchars(
                                taoDuongDanTrang(
                                    min($tongTrang, $page + 1),
                                    $tuKhoa
                                )
                            ) ?>"
                        >
                            Sau
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>