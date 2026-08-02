<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$currentPage = 'bai-viet';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: /dac-san-ca-mau/bai-viet.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT
        id,
        tieu_de,
        tom_tat,
        noi_dung,
        hinh_anh,
        ngay_dang
     FROM bai_viet
     WHERE id = :id
       AND trang_thai = 'xuat_ban'
     LIMIT 1"
);

$stmt->execute(['id' => $id]);

$baiViet = $stmt->fetch();

if (!$baiViet) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy bài viết';
} else {
    $pageTitle = $baiViet['tieu_de'];
}

$stmtLienQuan = $pdo->prepare(
    "SELECT id, tieu_de, hinh_anh, ngay_dang
     FROM bai_viet
     WHERE trang_thai = 'xuat_ban'
       AND id <> :id
     ORDER BY ngay_dang DESC, id DESC
     LIMIT 3"
);

$stmtLienQuan->execute(['id' => $id]);

$danhSachLienQuan = $stmtLienQuan->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
    .article-header {
        padding: 40px 0;
        background-color: #f2f7f4;
    }

    .article-main-image {
        width: 100%;
        max-height: 520px;
        object-fit: cover;
        border-radius: 14px;
    }

    .article-content {
        max-width: 850px;
        margin: 0 auto;
        font-size: 18px;
        line-height: 1.9;
    }

    .related-image,
    .related-no-image {
        width: 100%;
        height: 180px;
        object-fit: cover;
    }

    .related-no-image {
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #e9ecef;
        color: #6c757d;
    }
</style>

<?php if (!$baiViet): ?>
    <main class="container py-5">
        <div class="alert alert-warning text-center py-5">
            <h1 class="h3">Không tìm thấy bài viết</h1>

            <p>
                Bài viết không tồn tại hoặc chưa được xuất bản.
            </p>

            <a
                href="/dac-san-ca-mau/bai-viet.php"
                class="btn btn-success"
            >
                Quay lại danh sách
            </a>
        </div>
    </main>
<?php else: ?>
    <section class="article-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="/dac-san-ca-mau/">
                            Trang chủ
                        </a>
                    </li>

                    <li class="breadcrumb-item">
                        <a href="/dac-san-ca-mau/bai-viet.php">
                            Câu chuyện đặc sản
                        </a>
                    </li>

                    <li class="breadcrumb-item active">
                        <?= htmlspecialchars($baiViet['tieu_de']) ?>
                    </li>
                </ol>
            </nav>

            <h1 class="display-6 fw-bold text-success">
                <?= htmlspecialchars($baiViet['tieu_de']) ?>
            </h1>

            <?php if (!empty($baiViet['ngay_dang'])): ?>
                <p class="text-muted mb-0">
                    Ngày đăng:
                    <?= date(
                        'd/m/Y H:i',
                        strtotime($baiViet['ngay_dang'])
                    ) ?>
                </p>
            <?php endif; ?>
        </div>
    </section>

    <main class="container py-5">
        <?php if (!empty($baiViet['hinh_anh'])): ?>
            <img
                src="/dac-san-ca-mau/assets/uploads/bai-viet/<?= htmlspecialchars(
                    $baiViet['hinh_anh']
                ) ?>"
                alt="<?= htmlspecialchars($baiViet['tieu_de']) ?>"
                class="article-main-image shadow-sm mb-5"
            >
        <?php endif; ?>

        <article class="article-content">
            <?php if (!empty($baiViet['tom_tat'])): ?>
                <p class="lead fw-semibold text-secondary">
                    <?= nl2br(
                        htmlspecialchars($baiViet['tom_tat'])
                    ) ?>
                </p>

                <hr class="my-4">
            <?php endif; ?>

            <div>
                <?= nl2br(
                    htmlspecialchars($baiViet['noi_dung'])
                ) ?>
            </div>
        </article>

        <?php if (!empty($danhSachLienQuan)): ?>
            <section class="pt-5 mt-5 border-top">
                <h2 class="section-title mb-4">
                    Bài viết liên quan
                </h2>

                <div class="row g-4">
                    <?php foreach ($danhSachLienQuan as $lienQuan): ?>
                        <div class="col-md-4">
                            <div
                                class="card h-100 border-0
                                       shadow-sm overflow-hidden"
                            >
                                <?php if (!empty($lienQuan['hinh_anh'])): ?>
                                    <img
                                        src="/dac-san-ca-mau/assets/uploads/bai-viet/<?= htmlspecialchars(
                                            $lienQuan['hinh_anh']
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $lienQuan['tieu_de']
                                        ) ?>"
                                        class="related-image"
                                    >
                                <?php else: ?>
                                    <div class="related-no-image">
                                        Chưa có hình ảnh
                                    </div>
                                <?php endif; ?>

                                <div class="card-body d-flex flex-column">
                                    <h3 class="h6 fw-bold">
                                        <?= htmlspecialchars(
                                            $lienQuan['tieu_de']
                                        ) ?>
                                    </h3>

                                    <a
                                        href="/dac-san-ca-mau/chi-tiet-bai-viet.php?id=<?= (int) $lienQuan['id'] ?>"
                                        class="btn btn-outline-success mt-auto"
                                    >
                                        Xem bài viết
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>