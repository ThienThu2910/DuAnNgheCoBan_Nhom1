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
    $_SESSION['error'] = 'Mã bài viết không hợp lệ.';
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, tieu_de, hinh_anh
     FROM bai_viet
     WHERE id = :id
     LIMIT 1'
);

$stmt->execute(['id' => $id]);

$baiViet = $stmt->fetch();

if (!$baiViet) {
    $_SESSION['error'] = 'Không tìm thấy bài viết.';
    header('Location: index.php');
    exit;
}

try {
    $xoa = $pdo->prepare(
        'DELETE FROM bai_viet
         WHERE id = :id'
    );

    $xoa->execute(['id' => $id]);

    if (!empty($baiViet['hinh_anh'])) {
        $duongDanAnh =
            __DIR__
            . '/../../assets/uploads/bai-viet/'
            . basename($baiViet['hinh_anh']);

        if (is_file($duongDanAnh)) {
            unlink($duongDanAnh);
        }
    }

    $_SESSION['success'] =
        'Đã xóa bài viết “'
        . $baiViet['tieu_de']
        . '” thành công.';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Không thể xóa bài viết.';
}

header('Location: index.php');
exit;