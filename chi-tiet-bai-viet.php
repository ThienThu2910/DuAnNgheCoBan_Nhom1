<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$currentPage = 'bai-viet';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: /DuAnNgheCoBan_Nhom1/bai-viet.php');
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

// 1. Lấy danh sách ảnh album phụ từ database
$stmtAlbum = $pdo->prepare("SELECT duong_dan FROM hinh_anh_bai_viet WHERE bai_viet_id = :id ORDER BY thu_tu ASC, id ASC");
$stmtAlbum->execute(['id' => $id]);
$albumAnh = $stmtAlbum->fetchAll(PDO::FETCH_COLUMN);

// Gộp ảnh đại diện và album ảnh phụ
$tatCaAnh = [];
if (!empty($baiViet['hinh_anh'])) {
    $tatCaAnh[] = $baiViet['hinh_anh'];
}
if (!empty($albumAnh)) {
    $tatCaAnh = array_merge($tatCaAnh, $albumAnh);
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
        padding: 35px 0;
        background-color: #f2f7f4;
    }

    /* Giới hạn kích thước khung trình chiếu và thêm nền tối */
    .article-carousel-wrapper {
        max-width: 650px;
        margin: 0 auto 35px auto;
        background-color: #1a1a1a; /* Nền tối giúp ảnh nổi bật và không lộ khoảng trống */
        border-radius: 12px;
        overflow: hidden;
    }

    /* Hiển thị trọn vẹn 100% hình ảnh không bị cắt góc */
    .article-main-image {
        width: 100%;
        height: 380px;
        object-fit: contain; /* Giữ nguyên toàn bộ chi tiết ảnh */
        display: block;
    }

    /* Tùy biến nút điều hướng Trái / Phải */
    .carousel-control-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 40px;
        height: 40px;
        background-color: rgba(0, 0, 0, 0.6);
        border-radius: 50%;
        border: 2px solid #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
        opacity: 0.85;
        transition: all 0.2s ease-in-out;
        z-index: 5;
    }

    .carousel-control-btn:hover {
        background-color: rgba(25, 135, 84, 0.95);
        color: white;
        opacity: 1;
        transform: translateY(-50%) scale(1.08);
    }

    .carousel-control-prev-custom {
        left: 12px;
    }

    .carousel-control-next-custom {
        right: 12px;
    }

    .carousel-indicators [data-bs-target] {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #fff;
        border: 1px solid rgba(0,0,0,0.3);
    }

    .article-content {
        max-width: 850px;
        margin: 0 auto;
        font-size: 17px;
        line-height: 1.85;
    }

    .article-content img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin: 15px 0;
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
            <p>Bài viết không tồn tại hoặc chưa được xuất bản.</p>
            <a href="/DuAnNgheCoBan_Nhom1/bai-viet.php" class="btn btn-success">Quay lại danh sách</a>
        </div>
    </main>
<?php else: ?>
    <section class="article-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/DuAnNgheCoBan_Nhom1/">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="/DuAnNgheCoBan_Nhom1/bai-viet.php">Câu chuyện đặc sản</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($baiViet['tieu_de']) ?></li>
                </ol>
            </nav>

            <h1 class="h2 fw-bold text-success">
                <?= htmlspecialchars($baiViet['tieu_de']) ?>
            </h1>

            <?php if (!empty($baiViet['ngay_dang'])): ?>
                <p class="text-muted mb-0 small">
                    Ngày đăng: <?= date('d/m/Y H:i', strtotime($baiViet['ngay_dang'])) ?>
                </p>
            <?php endif; ?>
        </div>
    </section>

    <main class="container py-4">
        <!-- Khung trình chiếu hình ảnh -->
        <?php if (!empty($tatCaAnh)): ?>
            <div class="article-carousel-wrapper">
                <div id="articleCarousel" class="carousel slide shadow-sm rounded-3 overflow-hidden position-relative" data-bs-ride="carousel">
                    <?php if (count($tatCaAnh) > 1): ?>
                        <div class="carousel-indicators mb-2">
                            <?php foreach ($tatCaAnh as $idx => $anh): ?>
                                <button type="button" data-bs-target="#articleCarousel" data-bs-slide-to="<?= $idx ?>" class="<?= $idx === 0 ? 'active' : '' ?>" aria-current="<?= $idx === 0 ? 'true' : 'false' ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="carousel-inner">
                        <?php foreach ($tatCaAnh as $idx => $anh): ?>
                            <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
                                <img src="/DuAnNgheCoBan_Nhom1/assets/uploads/bai-viet/<?= htmlspecialchars($anh) ?>" class="article-main-image" alt="<?= htmlspecialchars($baiViet['tieu_de']) ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($tatCaAnh) > 1): ?>
                        <!-- Nút sang trái -->
                        <button class="carousel-control-btn carousel-control-prev-custom" type="button" data-bs-target="#articleCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Trước</span>
                        </button>
                        <!-- Nút sang phải -->
                        <button class="carousel-control-btn carousel-control-next-custom" type="button" data-bs-target="#articleCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Sau</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <article class="article-content">
            <?php if (!empty($baiViet['tom_tat'])): ?>
                <p class="lead fw-semibold text-secondary">
                    <?= nl2br(htmlspecialchars($baiViet['tom_tat'])) ?>
                </p>
                <hr class="my-4">
            <?php endif; ?>

            <div>
                <?= $baiViet['noi_dung'] ?>
            </div>
        </article>

        <?php if (!empty($danhSachLienQuan)): ?>
            <section class="pt-5 mt-5 border-top">
                <h2 class="h4 fw-bold mb-4">Bài viết liên quan</h2>
                <div class="row g-4">
                    <?php foreach ($danhSachLienQuan as $lienQuan): ?>
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm overflow-hidden">
                                <?php if (!empty($lienQuan['hinh_anh'])): ?>
                                    <img src="/DuAnNgheCoBan_Nhom1/assets/uploads/bai-viet/<?= htmlspecialchars($lienQuan['hinh_anh']) ?>" alt="<?= htmlspecialchars($lienQuan['tieu_de']) ?>" class="related-image">
                                <?php else: ?>
                                    <div class="related-no-image">Chưa có hình ảnh</div>
                                <?php endif; ?>

                                <div class="card-body d-flex flex-column">
                                    <h3 class="h6 fw-bold"><?= htmlspecialchars($lienQuan['tieu_de']) ?></h3>
                                    <a href="/DuAnNgheCoBan_Nhom1/chi-tiet-bai-viet.php?id=<?= (int) $lienQuan['id'] ?>" class="btn btn-outline-success btn-sm mt-auto">Xem bài viết</a>
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