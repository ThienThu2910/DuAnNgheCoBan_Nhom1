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
    $_SESSION['error'] = 'Mã cơ sở không hợp lệ.';
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, ten_co_so, hinh_anh
     FROM co_so_san_xuat
     WHERE id = :id
     LIMIT 1'
);

$stmt->execute(['id' => $id]);

$coSo = $stmt->fetch();

if (!$coSo) {
    $_SESSION['error'] = 'Không tìm thấy cơ sở cần xóa.';
    header('Location: index.php');
    exit;
}

try {
    $xoa = $pdo->prepare(
        'DELETE FROM co_so_san_xuat
         WHERE id = :id'
    );

    $xoa->execute(['id' => $id]);

    if (!empty($coSo['hinh_anh'])) {
        $duongDanAnh =
            __DIR__
            . '/../../assets/uploads/co-so/'
            . basename($coSo['hinh_anh']);

        if (is_file($duongDanAnh)) {
            unlink($duongDanAnh);
        }
    }

    $_SESSION['success'] =
        'Đã xóa cơ sở “'
        . $coSo['ten_co_so']
        . '” thành công.';
} catch (PDOException $e) {
    $_SESSION['error'] =
        'Không thể xóa cơ sở. Vui lòng kiểm tra lại dữ liệu.';
}

header('Location: index.php');
exit;