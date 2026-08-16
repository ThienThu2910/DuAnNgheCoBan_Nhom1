<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$pageTitle = 'Cơ sở sản xuất đặc sản Cà Mau';
$currentPage = 'co-so';

$tuKhoa = trim($_GET['q'] ?? '');

$dacSanId = filter_input(
    INPUT_GET,
    'dac_san_id',
    FILTER_VALIDATE_INT
);

/*
 * Lấy danh sách đặc sản để tạo bộ lọc.
 */
$stmtDacSan = $pdo->query(
    'SELECT id, ten_dac_san
     FROM dac_san
     WHERE trang_thai = 1
     ORDER BY ten_dac_san ASC'
);

$danhSachDacSan = $stmtDacSan->fetchAll();

/*
 * Lấy danh sách cơ sở.
 */
$sql = '
    SELECT
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
';

$params = [];

if ($tuKhoa !== '') {
    $sql .= '
        AND (
            cs.ten_co_so LIKE :tu_khoa
            OR cs.dia_chi LIKE :tu_khoa
        )
    ';

    $params['tu_khoa'] = '%' . $tuKhoa . '%';
}

if ($dacSanId) {
    $sql .= '
        AND EXISTS (
            SELECT 1
            FROM dac_san_co_so AS lk
            WHERE lk.co_so_id = cs.id
              AND lk.dac_san_id = :dac_san_id
        )
    ';

    $params['dac_san_id'] = $dacSanId;
}

$sql .= '
    GROUP BY
        cs.id,
        cs.ten_co_so,
        cs.dia_chi,
        cs.so_dien_thoai,
        cs.email,
        cs.mo_ta,
        cs.hinh_anh,
        cs.vi_do,
        cs.kinh_do,
        cs.google_maps_url

    ORDER BY cs.ten_co_so ASC
';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$danhSachCoSo = $stmt->fetchAll();

/*
 * Tạo URL mở Google Maps.
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
    .facility-banner {
        padding: 70px 0;
        color: #ffffff;
        text-align: center;
        background:
            linear-gradient(
                rgba(20, 77, 54, 0.85),
                rgba(20, 77, 54, 0.85)
            ),
            url("/DuAnNgheCoBan_Nhom1/assets/images/banner-ca-mau.jpg")
            center / cover no-repeat;
    }

    .facility-card {
        overflow: hidden;
        border: 0;
        border-radius: 14px;
        transition:
            transform 0.25s ease,
            box-shadow 0.25s ease;
    }

    .facility-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 14px 30px rgba(0, 0, 0, 0.12);
    }

    .facility-image,
    .facility-no-image {
        width: 100%;
        height: 230px;
        object-fit: cover;
    }

    .facility-no-image {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        background-color: #e9ecef;
    }

    .facility-description {
        display: -webkit-box;
        min-height: 72px;
        overflow: hidden;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
    }
</style>

<section class="facility-banner">
    <div class="container">
        <h1 class="fw-bold">
            Cơ sở sản xuất đặc sản Cà Mau
        </h1>

        <p class="lead mb-0">
            Khám phá các địa điểm và cơ sở gắn với đặc sản địa phương.
        </p>
    </div>
</section>

<main class="container py-5">
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-4">
            <form method="get" class="row g-3">
                <div class="col-lg-5">
                    <label for="q" class="form-label">
                        Tìm kiếm cơ sở
                    </label>

                    <input
                        type="text"
                        id="q"
                        name="q"
                        class="form-control"
                        placeholder="Nhập tên cơ sở hoặc địa chỉ..."
                        value="<?= htmlspecialchars($tuKhoa) ?>"
                    >
                </div>

                <div class="col-lg-5">
                    <label
                        for="dac_san_id"
                        class="form-label"
                    >
                        Lọc theo đặc sản
                    </label>

                    <select
                        id="dac_san_id"
                        name="dac_san_id"
                        class="form-select"
                    >
                        <option value="">
                            Tất cả đặc sản
                        </option>

                        <?php foreach ($danhSachDacSan as $dacSan): ?>
                            <option
                                value="<?= (int) $dacSan['id'] ?>"
                                <?= $dacSanId === (int) $dacSan['id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $dacSan['ten_dac_san']
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-2 d-flex align-items-end">
                    <div class="d-flex gap-2 w-100">
                        <button
                            type="submit"
                            class="btn btn-success flex-grow-1"
                        >
                            Lọc
                        </button>

                        <a
                            href="/DuAnNgheCoBan_Nhom1/co-so-san-xuat.php"
                            class="btn btn-outline-secondary"
                        >
                            Xóa
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div
        class="d-flex justify-content-between
               align-items-center flex-wrap gap-3 mb-4"
    >
        <div>
            <h2 class="section-title mb-1">
                Danh sách cơ sở
            </h2>

            <p class="text-muted mb-0">
                Tìm thấy <?= count($danhSachCoSo) ?> cơ sở.
            </p>
        </div>

        <a
            href="/DuAnNgheCoBan_Nhom1/ban-do.php<?= $dacSanId
                ? '?dac_san_id=' . (int) $dacSanId
                : '' ?>"
            class="btn btn-success"
        >
            Xem trên bản đồ
        </a>
    </div>

    <?php if (empty($danhSachCoSo)): ?>
        <div class="alert alert-warning text-center py-4">
            Không tìm thấy cơ sở phù hợp.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($danhSachCoSo as $coSo): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card facility-card h-100 shadow-sm">
                        <?php if (!empty($coSo['hinh_anh'])): ?>
                            <img
                                src="/DuAnNgheCoBan_Nhom1/assets/uploads/co-so/<?= htmlspecialchars(
                                    $coSo['hinh_anh']
                                ) ?>"
                                alt="<?= htmlspecialchars(
                                    $coSo['ten_co_so']
                                ) ?>"
                                class="facility-image"
                            >
                        <?php else: ?>
                            <div class="facility-no-image">
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

                            <?php if (!empty($coSo['danh_sach_dac_san'])): ?>
                                <p class="mb-2">
                                    <strong>Đặc sản:</strong><br>

                                    <span class="text-muted">
                                        <?= htmlspecialchars(
                                            $coSo['danh_sach_dac_san']
                                        ) ?>
                                    </span>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($coSo['mo_ta'])): ?>
                                <p class="text-muted facility-description">
                                    <?= htmlspecialchars($coSo['mo_ta']) ?>
                                </p>
                            <?php endif; ?>

                            <div class="d-flex gap-2 mt-auto">
                                <a
                                    href="<?= htmlspecialchars(
                                        taoGoogleMapsUrl($coSo)
                                    ) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-success flex-grow-1"
                                >
                                    Google Maps
                                </a>

                                <?php if (
                                    $coSo['vi_do'] !== null
                                    && $coSo['kinh_do'] !== null
                                ): ?>
                                    <a
                                        href="/DuAnNgheCoBan_Nhom1/ban-do.php?co_so_id=<?= (int) $coSo['id'] ?>"
                                        class="btn btn-outline-success"
                                    >
                                        Vị trí
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>