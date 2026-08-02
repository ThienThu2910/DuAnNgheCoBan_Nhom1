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

/* Lấy thông tin ảnh trước khi xóa */
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
    $xoa = $pdo->prepare(
        'DELETE FROM dac_san
         WHERE id = :id'
    );

    $xoa->execute(['id' => $id]);

    /* Xóa ảnh đại diện */
    if (!empty($dacSan['hinh_anh'])) {
        $duongDanAnh =
            __DIR__
            . '/../../assets/uploads/dac-san/'
            . basename($dacSan['hinh_anh']);

        if (is_file($duongDanAnh)) {
            unlink($duongDanAnh);
        }
    }

    $_SESSION['success'] =
        'Đã xóa đặc sản “'
        . $dacSan['ten_dac_san']
        . '” thành công.';
} catch (PDOException $e) {
    $_SESSION['error'] =
        'Không thể xóa đặc sản. Đặc sản có thể đang liên kết với dữ liệu khác.';
}

header('Location: index.php');
exit;