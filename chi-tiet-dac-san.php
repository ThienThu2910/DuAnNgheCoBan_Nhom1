<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$currentPage = 'dac-san';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: /dac-san-ca-mau/dac-san.php');
    exit;
}

/*
 * Lấy thông tin chi tiết đặc sản.
 */
$stmt = $pdo->prepare(
    'SELECT
        ds.id,
        ds.danh_muc_id,
        ds.ten_dac_san,
        ds.slug,
        ds.mo_ta_ngan,
        ds.nguon_goc,
        ds.mo_ta_chi_tiet,
        ds.cach_su_dung,
        ds.cach_bao_quan,
        ds.hinh_anh,
        ds.noi_bat,
        dm.ten_danh_muc
     FROM dac_san AS ds
     LEFT JOIN danh_muc AS dm
        ON dm.id = ds.danh_muc_id
     WHERE ds.id = :id
       AND ds.trang_thai = 1
     LIMIT 1'
);

$stmt->execute([
    'id' => $id
]);

$dacSan = $stmt->fetch();

if (!$dacSan) {
    http_response_code(404);

    $pageTitle = 'Không tìm thấy đặc sản';

    require_once __DIR__ . '/includes/header.php';
    require_once __DIR__ . '/includes/navbar.php';
    ?>

    <main class="container py-5">
        <div class="alert alert-warning text-center py-5">
            <h1 class="h3">Không tìm thấy đặc sản</h1>

            <p>
                Đặc sản không tồn tại hoặc đang tạm ẩn khỏi website.
            </p>

            <a
                href="/dac-san-ca-mau/dac-san.php"
                class="btn btn-success"
            >
                Quay lại danh sách
            </a>
        </div>
    </main>

    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $dacSan['ten_dac_san'] . ' - Đặc sản Cà Mau';

/*
 * Lấy các cơ sở có đặc sản này.
 */
$stmtCoSo = $pdo->prepare(
    'SELECT
        cs.id,
        cs.ten_co_so,
        cs.dia_chi,
        cs.so_dien_thoai,
        cs.email,
        cs.mo_ta,
        cs.hinh_anh,
        cs.vi_do,
        cs.kinh_do,
        cs.google_maps_url,
        dscs.ghi_chu
     FROM dac_san_co_so AS dscs
     INNER JOIN co_so_san_xuat AS cs
        ON cs.id = dscs.co_so_id
     WHERE dscs.dac_san_id = :dac_san_id
       AND cs.trang_thai = 1
     ORDER BY cs.ten_co_so ASC'
);

$stmtCoSo->execute([
    'dac_san_id' => $id
]);

$danhSachCoSo = $stmtCoSo->fetchAll();

/*
 * Lấy tối đa 3 đặc sản liên quan cùng danh mục.
 */
if (!empty($dacSan['danh_muc_id'])) {
    $stmtLienQuan = $pdo->prepare(
        'SELECT
            id,
            ten_dac_san,
            mo_ta_ngan,
            hinh_anh
         FROM dac_san
         WHERE trang_thai = 1
           AND danh_muc_id = :danh_muc_id
           AND id <> :id
         ORDER BY noi_bat DESC, id DESC
         LIMIT 3'
    );

    $stmtLienQuan->execute([
        'danh_muc_id' => $dacSan['danh_muc_id'],
        'id' => $id
    ]);
} else {
    $stmtLienQuan = $pdo->prepare(
        'SELECT
            id,
            ten_dac_san,
            mo_ta_ngan,
            hinh_anh
         FROM dac_san
         WHERE trang_thai = 1
           AND id <> :id
         ORDER BY noi_bat DESC, id DESC
         LIMIT 3'
    );

    $stmtLienQuan->execute([
        'id' => $id
    ]);
}

$danhSachLienQuan = $stmtLienQuan->fetchAll();

/*
 * Tạo đường dẫn Google Maps.
 */
function taoGoogleMapsUrl(array $coSo): string
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

<style>
    .detail-banner {
        padding: 28px 0;
        background-color: #f4f8f5;
        border-bottom: 1px solid #e2e8e4;
    }

    .detail-image {
        width: 100%;
        height: 450px;
        object-fit: cover;
        border-radius: 14px;
    }

    .detail-no-image {
        width: 100%;
        height: 450px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        color: #6c757d;
        background-color: #e9ecef;
    }

    .information-box {
        height: 100%;
        padding: 24px;
        border-radius: 12px;
        background-color: #f8faf8;
        border-left: 5px solid #198754;
    }

    .facility-card,
    .related-card {
        overflow: hidden;
        border: 0;
        border-radius: 12px;
    }

    .facility-image,
    .related-image,
    .related-no-image {
        width: 100%;
        height: 190px;
        object-fit: cover;
    }

    .related-no-image {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        background-color: #e9ecef;
    }

    .content-text {
        line-height: 1.8;
        white-space: normal;
    }

    @media (max-width: 768px) {
        .detail-image,
        .detail-no-image {
            height: 300px;
        }
    }
</style>

<section class="detail-banner">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item">
                    <a href="/dac-san-ca-mau/">
                        Trang chủ
                    </a>
                </li>

                <li class="breadcrumb-item">
                    <a href="/dac-san-ca-mau/dac-san.php">
                        Đặc sản
                    </a>
                </li>

                <li
                    class="breadcrumb-item active"
                    aria-current="page"
                >
                    <?= htmlspecialchars($dacSan['ten_dac_san']) ?>
                </li>
            </ol>
        </nav>

        <h1 class="fw-bold text-success mb-0">
            <?= htmlspecialchars($dacSan['ten_dac_san']) ?>
        </h1>
    </div>
</section>

<main class="container py-5">
    <section class="row g-5 align-items-start">
        <div class="col-lg-6">
            <?php if (!empty($dacSan['hinh_anh'])): ?>
                <img
                    src="/dac-san-ca-mau/assets/uploads/dac-san/<?= htmlspecialchars(
                        $dacSan['hinh_anh']
                    ) ?>"
                    alt="<?= htmlspecialchars($dacSan['ten_dac_san']) ?>"
                    class="detail-image shadow-sm"
                >
            <?php else: ?>
                <div class="detail-no-image">
                    Chưa có hình ảnh
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-6">
            <div class="mb-3">
                <span class="badge bg-success fs-6">
                    <?= htmlspecialchars(
                        $dacSan['ten_danh_muc'] ?? 'Chưa phân loại'
                    ) ?>
                </span>

                <?php if ((int) $dacSan['noi_bat'] === 1): ?>
                    <span class="badge bg-warning text-dark fs-6">
                        Đặc sản nổi bật
                    </span>
                <?php endif; ?>
            </div>

            <h2 class="display-6 fw-bold">
                <?= htmlspecialchars($dacSan['ten_dac_san']) ?>
            </h2>

            <p class="lead text-muted">
                <?= htmlspecialchars(
                    $dacSan['mo_ta_ngan']
                        ?: 'Thông tin giới thiệu đang được cập nhật.'
                ) ?>
            </p>

            <?php if (!empty($dacSan['nguon_goc'])): ?>
                <div class="information-box mt-4">
                    <h3 class="h5 text-success">
                        Nguồn gốc
                    </h3>

                    <div class="content-text">
                        <?= nl2br(
                            htmlspecialchars($dacSan['nguon_goc'])
                        ) ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <a
                    href="#co-so-san-xuat"
                    class="btn btn-success"
                >
                    Xem địa điểm
                </a>

                <a
                    href="/dac-san-ca-mau/dac-san.php"
                    class="btn btn-outline-secondary"
                >
                    Xem đặc sản khác
                </a>
            </div>
        </div>
    </section>

    <?php if (!empty($dacSan['mo_ta_chi_tiet'])): ?>
        <section class="py-5 border-bottom">
            <h2 class="section-title mb-4">
                Giới thiệu chi tiết
            </h2>

            <div class="content-text">
                <?= nl2br(
                    htmlspecialchars($dacSan['mo_ta_chi_tiet'])
                ) ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (
        !empty($dacSan['cach_su_dung'])
        || !empty($dacSan['cach_bao_quan'])
    ): ?>
        <section class="py-5 border-bottom">
            <div class="row g-4">
                <?php if (!empty($dacSan['cach_su_dung'])): ?>
                    <div class="col-md-6">
                        <div class="information-box">
                            <h2 class="h4 text-success">
                                Cách sử dụng
                            </h2>

                            <div class="content-text">
                                <?= nl2br(
                                    htmlspecialchars(
                                        $dacSan['cach_su_dung']
                                    )
                                ) ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($dacSan['cach_bao_quan'])): ?>
                    <div class="col-md-6">
                        <div class="information-box">
                            <h2 class="h4 text-success">
                                Cách bảo quản
                            </h2>

                            <div class="content-text">
                                <?= nl2br(
                                    htmlspecialchars(
                                        $dacSan['cach_bao_quan']
                                    )
                                ) ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <section id="co-so-san-xuat" class="py-5 border-bottom">
        <div class="mb-4">
            <h2 class="section-title mb-1">
                Địa điểm và cơ sở giới thiệu
            </h2>

            <p class="text-muted mb-0">
                Thông tin các địa điểm có liên quan đến đặc sản này.
            </p>
        </div>

        <?php if (empty($danhSachCoSo)): ?>
            <div class="alert alert-info">
                Địa điểm của đặc sản này đang được cập nhật.
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($danhSachCoSo as $coSo): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card facility-card h-100 shadow-sm">
                            <?php if (!empty($coSo['hinh_anh'])): ?>
                                <img
                                    src="/dac-san-ca-mau/assets/uploads/co-so/<?= htmlspecialchars(
                                        $coSo['hinh_anh']
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $coSo['ten_co_so']
                                    ) ?>"
                                    class="facility-image"
                                >
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column">
                                <h3 class="h5 fw-bold">
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

                                <?php if (!empty($coSo['so_dien_thoai'])): ?>
                                    <p class="mb-2">
                                        <strong>Điện thoại:</strong>

                                        <a
                                            href="tel:<?= htmlspecialchars(
                                                $coSo['so_dien_thoai']
                                            ) ?>"
                                        >
                                            <?= htmlspecialchars(
                                                $coSo['so_dien_thoai']
                                            ) ?>
                                        </a>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($coSo['ghi_chu'])): ?>
                                    <p class="text-muted">
                                        <?= htmlspecialchars(
                                            $coSo['ghi_chu']
                                        ) ?>
                                    </p>
                                <?php endif; ?>

                                <a
                                    href="<?= htmlspecialchars(
                                        taoGoogleMapsUrl($coSo)
                                    ) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-success mt-auto"
                                >
                                    Xem trên Google Maps
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if (!empty($danhSachLienQuan)): ?>
        <section class="pt-5">
            <h2 class="section-title mb-4">
                Đặc sản liên quan
            </h2>

            <div class="row g-4">
                <?php foreach ($danhSachLienQuan as $lienQuan): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card related-card h-100 shadow-sm">
                            <?php if (!empty($lienQuan['hinh_anh'])): ?>
                                <img
                                    src="/dac-san-ca-mau/assets/uploads/dac-san/<?= htmlspecialchars(
                                        $lienQuan['hinh_anh']
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $lienQuan['ten_dac_san']
                                    ) ?>"
                                    class="related-image"
                                >
                            <?php else: ?>
                                <div class="related-no-image">
                                    Chưa có hình ảnh
                                </div>
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column">
                                <h3 class="h5 fw-bold">
                                    <?= htmlspecialchars(
                                        $lienQuan['ten_dac_san']
                                    ) ?>
                                </h3>

                                <p class="text-muted">
                                    <?= htmlspecialchars(
                                        $lienQuan['mo_ta_ngan']
                                            ?: 'Thông tin đang được cập nhật.'
                                    ) ?>
                                </p>

                                <a
                                    href="/dac-san-ca-mau/chi-tiet-dac-san.php?id=<?= (int) $lienQuan['id'] ?>"
                                    class="btn btn-outline-success mt-auto"
                                >
                                    Xem chi tiết
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>