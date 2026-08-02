<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['error'] = 'Mã cơ sở không hợp lệ.';
    header('Location: index.php');
    exit;
}

$stmtCoSo = $pdo->prepare(
    'SELECT id, ten_co_so, dia_chi
     FROM co_so_san_xuat
     WHERE id = :id
     LIMIT 1'
);

$stmtCoSo->execute(['id' => $id]);

$coSo = $stmtCoSo->fetch();

if (!$coSo) {
    $_SESSION['error'] = 'Không tìm thấy cơ sở.';
    header('Location: index.php');
    exit;
}

$stmtDacSan = $pdo->query(
    'SELECT
        ds.id,
        ds.ten_dac_san,
        dm.ten_danh_muc
     FROM dac_san AS ds
     LEFT JOIN danh_muc AS dm
        ON dm.id = ds.danh_muc_id
     ORDER BY dm.ten_danh_muc ASC, ds.ten_dac_san ASC'
);

$danhSachDacSan = $stmtDacSan->fetchAll();

$stmtDaLienKet = $pdo->prepare(
    'SELECT dac_san_id
     FROM dac_san_co_so
     WHERE co_so_id = :co_so_id'
);

$stmtDaLienKet->execute([
    'co_so_id' => $id
]);

$dacSanDaLienKet = array_map(
    'intval',
    array_column($stmtDaLienKet->fetchAll(), 'dac_san_id')
);

$loi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $danhSachId = $_POST['dac_san_ids'] ?? [];

    if (!is_array($danhSachId)) {
        $danhSachId = [];
    }

    $danhSachId = array_map('intval', $danhSachId);

    $danhSachId = array_values(
        array_unique(
            array_filter(
                $danhSachId,
                static fn (int $dacSanId): bool => $dacSanId > 0
            )
        )
    );

    try {
        $pdo->beginTransaction();

        $xoaLienKetCu = $pdo->prepare(
            'DELETE FROM dac_san_co_so
             WHERE co_so_id = :co_so_id'
        );

        $xoaLienKetCu->execute([
            'co_so_id' => $id
        ]);

        if (!empty($danhSachId)) {
            $themLienKet = $pdo->prepare(
                'INSERT INTO dac_san_co_so
                    (dac_san_id, co_so_id)
                 VALUES
                    (:dac_san_id, :co_so_id)'
            );

            foreach ($danhSachId as $dacSanId) {
                $themLienKet->execute([
                    'dac_san_id' => $dacSanId,
                    'co_so_id' => $id
                ]);
            }
        }

        $pdo->commit();

        $_SESSION['success'] =
            'Cập nhật liên kết đặc sản thành công.';

        header('Location: index.php');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $loi = 'Không thể cập nhật liên kết: '
            . $e->getMessage();

        $dacSanDaLienKet = $danhSachId;
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

    <title>Liên kết đặc sản</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
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
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div
                            class="d-flex justify-content-between
                                   align-items-center flex-wrap gap-2 mb-4"
                        >
                            <div>
                                <h1 class="h3 mb-1">
                                    Liên kết đặc sản
                                </h1>

                                <p class="text-muted mb-0">
                                    Cơ sở:
                                    <strong>
                                        <?= htmlspecialchars(
                                            $coSo['ten_co_so']
                                        ) ?>
                                    </strong>
                                </p>

                                <p class="text-muted mb-0">
                                    <?= htmlspecialchars($coSo['dia_chi']) ?>
                                </p>
                            </div>

                            <a
                                href="index.php"
                                class="btn btn-secondary"
                            >
                                Quay lại
                            </a>
                        </div>

                        <?php if ($loi !== ''): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($loi) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($danhSachDacSan)): ?>
                            <div class="alert alert-warning">
                                Chưa có đặc sản nào trong hệ thống.

                                <a
                                    href="/dac-san-ca-mau/admin/dac-san/create.php"
                                    class="alert-link"
                                >
                                    Thêm đặc sản
                                </a>
                            </div>
                        <?php else: ?>
                            <form method="post">
                                <div
                                    class="d-flex justify-content-between
                                           align-items-center mb-3"
                                >
                                    <p class="mb-0">
                                        Chọn những đặc sản được giới thiệu
                                        tại cơ sở này:
                                    </p>

                                    <button
                                        type="button"
                                        id="selectAll"
                                        class="btn btn-outline-success btn-sm"
                                    >
                                        Chọn tất cả
                                    </button>
                                </div>

                                <div class="row g-3">
                                    <?php foreach (
                                        $danhSachDacSan as $dacSan
                                    ): ?>
                                        <div class="col-md-6">
                                            <div
                                                class="border rounded
                                                       p-3 h-100 bg-white"
                                            >
                                                <div class="form-check">
                                                    <input
                                                        type="checkbox"
                                                        id="dac_san_<?= (int) $dacSan['id'] ?>"
                                                        name="dac_san_ids[]"
                                                        value="<?= (int) $dacSan['id'] ?>"
                                                        class="form-check-input specialty-checkbox"
                                                        <?= in_array(
                                                            (int) $dacSan['id'],
                                                            $dacSanDaLienKet,
                                                            true
                                                        )
                                                            ? 'checked'
                                                            : '' ?>
                                                    >

                                                    <label
                                                        for="dac_san_<?= (int) $dacSan['id'] ?>"
                                                        class="form-check-label"
                                                    >
                                                        <strong>
                                                            <?= htmlspecialchars(
                                                                $dacSan['ten_dac_san']
                                                            ) ?>
                                                        </strong>

                                                        <br>

                                                        <span class="text-muted small">
                                                            <?= htmlspecialchars(
                                                                $dacSan['ten_danh_muc']
                                                                    ?? 'Chưa phân loại'
                                                            ) ?>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <button
                                    type="submit"
                                    class="btn btn-success mt-4"
                                >
                                    Lưu liên kết
                                </button>

                                <a
                                    href="index.php"
                                    class="btn btn-secondary mt-4"
                                >
                                    Hủy
                                </a>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const selectAllButton = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll(
            '.specialty-checkbox'
        );

        if (selectAllButton) {
            selectAllButton.addEventListener('click', function () {
                const tatCaDaChon = Array.from(checkboxes).every(
                    checkbox => checkbox.checked
                );

                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = !tatCaDaChon;
                });

                this.textContent = tatCaDaChon
                    ? 'Chọn tất cả'
                    : 'Bỏ chọn tất cả';
            });
        }
    </script>
</body>
</html>
