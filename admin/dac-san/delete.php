<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['error'] = 'Mã đặc sản không hợp lệ.';
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, ten_dac_san, hinh_anh
     FROM dac_san
     WHERE id = :id
     LIMIT 1'
);
$stmt->execute(['id' => $id]);
$dacSan = $stmt->fetch();

if (!$dacSan) {
    $_SESSION['error'] = 'Không tìm thấy đặc sản cần xóa.';
    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Dọn dẹp liên kết cơ sở sản xuất trong dac_san_co_so
    $stmtXoaLienKet = $pdo->prepare('DELETE FROM dac_san_co_so WHERE dac_san_id = :id');
    $stmtXoaLienKet->execute(['id' => $id]);

    // 2. Lấy danh sách ảnh phụ trong gallery để xóa file vật lý
    $stmtAnhPhu = $pdo->prepare('SELECT duong_dan FROM hinh_anh_dac_san WHERE dac_san_id = :id');
    $stmtAnhPhu->execute(['id' => $id]);
    $dsAnhPhu = $stmtAnhPhu->fetchAll(PDO::FETCH_COLUMN);

    $stmtXoaGallery = $pdo->prepare('DELETE FROM hinh_anh_dac_san WHERE dac_san_id = :id');
    $stmtXoaGallery->execute(['id' => $id]);

    // 3. Xóa bản ghi đặc sản
    $xoa = $pdo->prepare('DELETE FROM dac_san WHERE id = :id');
    $xoa->execute(['id' => $id]);

    $pdo->commit();

    // 4. Xóa ảnh đại diện chính
    if (!empty($dacSan['hinh_anh'])) {
        $duongDanAnh = __DIR__ . '/../../assets/uploads/dac-san/' . basename($dacSan['hinh_anh']);
        if (is_file($duongDanAnh)) {
            unlink($duongDanAnh);
        }
    }

    // 5. Xóa các file ảnh phụ trong thư mục uploads
    foreach ($dsAnhPhu as $tenAnh) {
        $filePhu = __DIR__ . '/../../assets/uploads/dac-san/' . basename($tenAnh);
        if (is_file($filePhu)) {
            unlink($filePhu);
        }
    }

    $_SESSION['success'] = 'Đã xóa đặc sản “' . $dacSan['ten_dac_san'] . '” thành công.';
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = 'Không thể xóa đặc sản: ' . $e->getMessage();
}

header('Location: index.php');
exit;