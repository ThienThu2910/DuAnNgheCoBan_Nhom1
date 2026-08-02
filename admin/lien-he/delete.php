<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$csrfToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrfToken)
    || empty($_SESSION['csrf_admin_contact'])
    || !hash_equals(
        $_SESSION['csrf_admin_contact'],
        $csrfToken
    )
) {
    $_SESSION['error'] = 'Phiên xóa không hợp lệ.';
    header('Location: index.php');
    exit;
}

if (!$id) {
    $_SESSION['error'] = 'Mã liên hệ không hợp lệ.';
    header('Location: index.php');
    exit;
}

$xoa = $pdo->prepare(
    'DELETE FROM lien_he
     WHERE id = :id'
);

$xoa->execute(['id' => $id]);

if ($xoa->rowCount() > 0) {
    $_SESSION['success'] = 'Xóa liên hệ thành công.';
} else {
    $_SESSION['error'] = 'Không tìm thấy liên hệ cần xóa.';
}

header('Location: index.php');
exit;