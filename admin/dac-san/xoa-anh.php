<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';

$anhId = filter_input(INPUT_POST, 'anh_id', FILTER_VALIDATE_INT);
$dacSanId = filter_input(INPUT_POST, 'dac_san_id', FILTER_VALIDATE_INT);

if (!$anhId || !$dacSanId) {
    header('Location: index.php');
    exit;
}

// 1. Đổi 'hinh_anh' thành 'duong_dan'
$stmt = $pdo->prepare('SELECT duong_dan FROM hinh_anh_dac_san WHERE id = :id AND dac_san_id = :dac_san_id');
$stmt->execute(['id' => $anhId, 'dac_san_id' => $dacSanId]);
$anh = $stmt->fetch();

if ($anh) {
    // 2. Đổi key truy xuất mảng thành $anh['duong_dan']
    $filePath = __DIR__ . '/../../assets/uploads/dac-san/' . $anh['duong_dan'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    $stmtDelete = $pdo->prepare('DELETE FROM hinh_anh_dac_san WHERE id = :id');
    $stmtDelete->execute(['id' => $anhId]);
    $_SESSION['success'] = 'Đã xóa ảnh phụ thành công!';
}

header('Location: edit.php?id=' . $dacSanId);
exit;