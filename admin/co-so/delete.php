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

// 1. Kiểm tra cơ sở tồn tại
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
    // 2. Bắt đầu Transaction để xóa an toàn
    $pdo->beginTransaction();

    // 3. Xóa các liên kết trong bảng dac_san_co_so trước để tránh lỗi khóa ngoại
    $xoaLienKet = $pdo->prepare('DELETE FROM dac_san_co_so WHERE co_so_id = :id');
    $xoaLienKet->execute(['id' => $id]);

    // 4. Xóa cơ sở sản xuất
    $xoaCoSo = $pdo->prepare('DELETE FROM co_so_san_xuat WHERE id = :id');  
    $xoaCoSo->execute(['id' => $id]);

    // 5. Commit dữ liệu
    $pdo->commit();

    // 6. Xóa tệp ảnh đính kèm nếu có
    if (!empty($coSo['hinh_anh'])) {    
        $duongDanAnh = __DIR__ . '/../../assets/uploads/co-so/' . basename($coSo['hinh_anh']);  
        if (is_file($duongDanAnh)) {    
            unlink($duongDanAnh);    
        }    
    }

    $_SESSION['success'] = 'Đã xóa cơ sở “' . $coSo['ten_co_so'] . '” thành công.';    
} catch (PDOException $e) {    
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Ghi log lỗi nếu cần và thông báo cho người dùng
    $_SESSION['error'] = 'Không thể xóa cơ sở. Chi tiết: ' . $e->getMessage();    
}

header('Location: index.php');    
exit;