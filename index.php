<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$pageTitle = 'Đặc sản Cà Mau - Tinh hoa vùng đất cực Nam';
$currentPage = 'trang-chu';
$baseUrl = '/DuAnNgheCoBan_Nhom1';

/*
 * Lấy 6 đặc sản nổi bật.
 */
$stmtDacSan = $pdo->query(
    'SELECT
        ds.id,
        ds.ten_dac_san,
        ds.mo_ta_ngan,
        ds.hinh_anh,
        ds.noi_bat,
        dm.ten_danh_muc
     FROM dac_san AS ds
     LEFT JOIN danh_muc AS dm
        ON dm.id = ds.danh_muc_id
     WHERE ds.trang_thai = 1
     ORDER BY ds.noi_bat DESC, ds.id DESC
     LIMIT 6'
);

$danhSachDacSan = $stmtDacSan->fetchAll();

/*
 * Lấy 3 cơ sở sản xuất mới nhất.
 */
$stmtCoSo = $pdo->query(
    'SELECT
        cs.id,
        cs.ten_co_so,
        cs.dia_chi,
        cs.mo_ta,
        cs.hinh_anh,
        cs.vi_do,
        cs.kinh_do,
        cs.google_maps_url,

        GROUP_CONCAT(
            DISTINCT ds.ten_dac_san
            ORDER BY ds.ten_dac_san
            SEPARATOR ", "
        ) AS danh_sach_dac_san

     FROM co_so_san_xuat AS cs

     LEFT JOIN dac_san_co_so AS dscs
        ON dscs.co_so_id = cs.id

     LEFT JOIN dac_san AS ds
        ON ds.id = dscs.dac_san_id
       AND ds.trang_thai = 1

     WHERE cs.trang_thai = 1

     GROUP BY
        cs.id,
        cs.ten_co_so,
        cs.dia_chi,
        cs.mo_ta,
        cs.hinh_anh,
        cs.vi_do,
        cs.kinh_do,
        cs.google_maps_url

     ORDER BY cs.id DESC
     LIMIT 3'
);

$danhSachCoSo = $stmtCoSo->fetchAll();

/*
 * Lấy 3 bài viết mới nhất đã xuất bản.
 */
$stmtBaiViet = $pdo->query(
    "SELECT
        id,
        tieu_de,
        tom_tat,
        hinh_anh,
        ngay_dang
     FROM bai_viet
     WHERE trang_thai = 'xuat_ban'
     ORDER BY ngay_dang DESC, id DESC
     LIMIT 3"
);

$danhSachBaiViet = $stmtBaiViet->fetchAll();

function taoGoogleMapsUrlTrangChu(array $coSo): string
{
    if (!empty($coSo['google_maps_url'])) {
        return $coSo['google_maps_url'];
    }

    if (
        $coSo['vi_do'] !== null
        && $coSo['kinh_do'] !== null
    ) {
        return 'https://www.google.com/maps/search/?api=1&query='
            . rawurlencode(
                $coSo['vi_do'] . ',' . $coSo['kinh_do']
            );
    }

    return 'https://www.google.com/maps/search/?api=1&query='
        . rawurlencode(
            $coSo['ten_co_so'] . ', ' . $coSo['dia_chi']
        );
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>



<!-- Banner chính -->
<section class="home-hero">
    <div class="container">
        <p class="text-warning text-uppercase fw-bold mb-2">
            Tinh hoa vùng đất cực Nam
        </p>

        <h1>
            Khám phá đặc sản và văn hóa ẩm thực Cà Mau
        </h1>

        <p class="lead mt-4">
            Tìm hiểu nguồn gốc, cách sử dụng, cơ sở sản xuất và những
            câu chuyện gắn với các sản vật nổi tiếng của Cà Mau.
        </p>

        <div class="d-flex flex-wrap gap-3 mt-4 justify-content-md-start justify-content-center">
            <a
                href="<?= $baseUrl ?>/dac-san.php"
                class="btn btn-warning btn-lg"
            >
                Khám phá đặc sản
            </a>

            <a
                href="<?= $baseUrl ?>/ban-do.php"
                class="btn btn-outline-light btn-lg"
            >
                Xem bản đồ
            </a>
        </div>
    </div>
</section>

<!-- Giới thiệu -->
<section class="home-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <p class="text-success fw-bold text-uppercase">
                    Về đặc sản Cà Mau
                </p>

                <h2 class="home-title display-6">
                    Hương vị từ rừng, biển và vùng đất phương Nam
                </h2>
            </div>

            <div class="col-lg-6">
                <p class="lead text-muted mb-0">
                    Cà Mau nổi tiếng với cua biển, tôm khô, ba khía,
                    khô cá bổi, mật ong rừng U Minh, dưa bồn bồn và
                    nhiều sản vật đặc trưng khác. Mỗi đặc sản đều gắn
                    với môi trường tự nhiên, nghề truyền thống và đời
                    sống văn hóa của người dân địa phương.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Đặc sản nổi bật -->
<section class="home-section home-section-light">
    <div class="container">
        <div
            class="d-flex justify-content-between
                   align-items-end flex-wrap gap-3 mb-5"
        >
            <div>
                <p class="text-success fw-bold text-uppercase mb-2">
                    Sản vật địa phương
                </p>

                <h2 class="home-title mb-0">
                    Đặc sản nổi bật
                </h2>
            </div>

            <a
                href="<?= $baseUrl ?>/dac-san.php"
                class="btn btn-outline-success"
            >
                Xem tất cả đặc sản
            </a>
        </div>

        <?php if (empty($danhSachDacSan)): ?>
            <div class="alert alert-info">
                Danh sách đặc sản đang được cập nhật.
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($danhSachDacSan as $dacSan): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card specialty-card h-100 shadow-sm">
                            <div class="position-relative">
                                <?php if (!empty($dacSan['hinh_anh'])): ?>
                                    <img
                                        src="<?= $baseUrl ?>/assets/uploads/dac-san/<?= htmlspecialchars(
                                            $dacSan['hinh_anh']
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $dacSan['ten_dac_san']
                                        ) ?>"
                                        class="specialty-image"
                                    >
                                <?php else: ?>
                                    <div class="card-no-image">
                                        Chưa có hình ảnh
                                    </div>
                                <?php endif; ?>

                                <?php if ((int) $dacSan['noi_bat'] === 1): ?>
                                    <span
                                        class="badge bg-warning text-dark
                                               position-absolute top-0 end-0 m-3"
                                    >
                                        Nổi bật
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <span
                                        class="badge bg-success-subtle text-success"
                                    >
                                        <?= htmlspecialchars(
                                            $dacSan['ten_danh_muc']
                                                ?? 'Chưa phân loại'
                                        ) ?>
                                    </span>
                                </div>

                                <h3 class="h5 fw-bold">
                                    <?= htmlspecialchars(
                                        $dacSan['ten_dac_san']
                                    ) ?>
                                </h3>

                                <p class="text-muted limited-text">
                                    <?= htmlspecialchars(
                                        $dacSan['mo_ta_ngan']
                                            ?: 'Thông tin đang được cập nhật.'
                                    ) ?>
                                </p>

                                <a
                                    href="<?= $baseUrl ?>/chi-tiet-dac-san.php?id=<?= (int) $dacSan['id'] ?>"
                                    class="btn btn-success mt-auto"
                                >
                                    Xem chi tiết
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Kêu gọi xem bản đồ -->
<section class="home-section">
    <div class="container">
        <div class="map-callout shadow-sm">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <p class="text-warning fw-bold text-uppercase mb-2">
                        Bản đồ đặc sản
                    </p>

                    <h2 class="fw-bold">
                        Tìm vị trí các cơ sở và địa điểm đặc sản Cà Mau
                    </h2>

                    <p class="lead mb-0">
                        Chọn từng loại đặc sản để xem những địa điểm có
                        liên quan trực tiếp trên bản đồ tương tác.
                    </p>
                </div>

                <div class="col-lg-4 text-lg-end">
                    <a
                        href="<?= $baseUrl ?>/ban-do.php"
                        class="btn btn-warning btn-lg"
                    >
                        Mở bản đồ đặc sản
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cơ sở sản xuất -->
<section class="home-section home-section-light">
    <div class="container">
        <div
            class="d-flex justify-content-between
                   align-items-end flex-wrap gap-3 mb-5"
        >
            <div>
                <p class="text-success fw-bold text-uppercase mb-2">
                    Địa điểm tiêu biểu
                </p>

                <h2 class="home-title mb-0">
                    Cơ sở sản xuất và giới thiệu
                </h2>
            </div>

            <a
                href="<?= $baseUrl ?>/co-so-san-xuat.php"
                class="btn btn-outline-success"
            >
                Xem tất cả cơ sở
            </a>
        </div>

        <?php if (empty($danhSachCoSo)): ?>
            <div class="alert alert-info">
                Danh sách cơ sở sản xuất đang được cập nhật.
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($danhSachCoSo as $coSo): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card facility-card h-100 shadow-sm">
                            <?php if (!empty($coSo['hinh_anh'])): ?>
                                <img
                                    src="<?= $baseUrl ?>/assets/uploads/co-so/<?= htmlspecialchars(
                                        $coSo['hinh_anh']
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $coSo['ten_co_so']
                                    ) ?>"
                                    class="facility-image"
                                >
                            <?php else: ?>
                                <div class="card-no-image">
                                    Chưa có hình ảnh
                                </div>
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column">
                                <h3 class="h5 fw-bold text-success">
                                    <?= htmlspecialchars(
                                        $coSo['ten_co_so']
                                    ) ?>
                                </h3>

                                <p class="mb-2">
                                    <strong>Địa chỉ:</strong><br>

                                    <?= htmlspecialchars(
                                        $coSo['dia_chi']
                                    ) ?>
                                </p>

                                <?php if (!empty($coSo['danh_sach_dac_san'])): ?>
                                    <p class="small text-muted limited-text">
                                        <strong>Đặc sản:</strong><br>

                                        <?= htmlspecialchars(
                                            $coSo['danh_sach_dac_san']
                                        ) ?>
                                    </p>
                                <?php endif; ?>

                                <a
                                    href="<?= htmlspecialchars(
                                        taoGoogleMapsUrlTrangChu($coSo)
                                    ) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-outline-success mt-auto"
                                >
                                    Xem trên Google Maps
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Bài viết mới -->
<section class="home-section">
    <div class="container">
        <div
            class="d-flex justify-content-between
                   align-items-end flex-wrap gap-3 mb-5"
        >
            <div>
                <p class="text-success fw-bold text-uppercase mb-2">
                    Văn hóa và con người
                </p>

                <h2 class="home-title mb-0">
                    Câu chuyện đặc sản mới nhất
                </h2>
            </div>

            <a
                href="<?= $baseUrl ?>/bai-viet.php"
                class="btn btn-outline-success"
            >
                Xem tất cả bài viết
            </a>
        </div>

        <?php if (empty($danhSachBaiViet)): ?>
            <div class="alert alert-info">
                Các câu chuyện đặc sản đang được cập nhật.
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($danhSachBaiViet as $baiViet): ?>
                    <div class="col-md-6 col-lg-4">
                        <article class="card article-card h-100 shadow-sm">
                            <?php if (!empty($baiViet['hinh_anh'])): ?>
                                <img
                                    src="<?= $baseUrl ?>/assets/uploads/bai-viet/<?= htmlspecialchars(
                                        $baiViet['hinh_anh']
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $baiViet['tieu_de']
                                    ) ?>"
                                    class="article-image"
                                >
                            <?php else: ?>
                                <div class="card-no-image">
                                    Chưa có hình ảnh
                                </div>
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column">
                                <?php if (!empty($baiViet['ngay_dang'])): ?>
                                    <p class="small text-muted mb-2">
                                        <?= date(
                                            'd/m/Y',
                                            strtotime(
                                                $baiViet['ngay_dang']
                                            )
                                        ) ?>
                                    </p>
                                <?php endif; ?>

                                <h3 class="h5 fw-bold">
                                    <?= htmlspecialchars(
                                        $baiViet['tieu_de']
                                    ) ?>
                                </h3>

                                <p class="text-muted limited-text">
                                    <?= htmlspecialchars(
                                        $baiViet['tom_tat']
                                            ?: 'Nội dung đang được cập nhật.'
                                    ) ?>
                                </p>

                                <a
                                    href="<?= $baseUrl ?>/chi-tiet-bai-viet.php?id=<?= (int) $baiViet['id'] ?>"
                                    class="btn btn-outline-success mt-auto"
                                >
                                    Đọc bài viết
                                </a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>