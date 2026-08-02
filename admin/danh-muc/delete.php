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
    $_SESSION['success'] = 'Mã danh mục không hợp lệ.';
    header('Location: index.php');
    exit;
}

$kiemTra = $pdo->prepare(
    'SELECT COUNT(*) AS tong
     FROM dac_san
     WHERE danh_muc_id = :id'
);

$kiemTra->execute(['id' => $id]);

$ketQua = $kiemTra->fetch();

if ((int) $ketQua['tong'] > 0) {
    $_SESSION['success'] =
        'Không thể xóa vì danh mục đang có đặc sản.';
} else {
    $xoa = $pdo->prepare(
        'DELETE FROM danh_muc
         WHERE id = :id'
    );

    $xoa->execute(['id' => $id]);

    $_SESSION['success'] = 'Xóa danh mục thành công.';
}

header('Location: index.php');
exit;