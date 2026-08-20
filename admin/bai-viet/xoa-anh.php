<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';

$anhId = filter_input(INPUT_POST, 'anh_id', FILTER_VALIDATE_INT);
$baiVietId = filter_input(INPUT_POST, 'bai_viet_id', FILTER_VALIDATE_INT);

if (!$anhId || !$baiVietId) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT duong_dan FROM hinh_anh_bai_viet WHERE id = :id AND bai_viet_id = :bai_viet_id');
$stmt->execute(['id' => $anhId, 'bai_viet_id' => $baiVietId]);
$anh = $stmt->fetch();

if ($anh) {
    $filePath = __DIR__ . '/../../assets/uploads/bai-viet/' . $anh['duong_dan'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    $stmtDelete = $pdo->prepare('DELETE FROM hinh_anh_bai_viet WHERE id = :id');
    $stmtDelete->execute(['id' => $anhId]);
    $_SESSION['success'] = 'Đã xóa ảnh phụ thành công!';
}

header('Location: edit.php?id=' . $baiVietId);
exit;