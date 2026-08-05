<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');

if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $searchParam = '%' . $q . '%';

    // Đổi các tên tham số riêng biệt (:q1, :q2, :q3, :q4) để tránh lỗi PDO
    $stmt = $pdo->prepare('
        SELECT id, ten_dac_san AS ten, hinh_anh, "dac-san" AS loai 
        FROM dac_san 
        WHERE trang_thai = 1 
          AND (ten_dac_san LIKE :q1 OR mo_ta_ngan LIKE :q2)
        
        UNION ALL
        
        SELECT id, ten_co_so AS ten, hinh_anh, "co-so" AS loai 
        FROM co_so_san_xuat 
        WHERE trang_thai = 1 
          AND (ten_co_so LIKE :q3 OR dia_chi LIKE :q4)
        
        LIMIT 6
    ');

    $stmt->execute([
        'q1' => $searchParam,
        'q2' => $searchParam,
        'q3' => $searchParam,
        'q4' => $searchParam
    ]);

    $ketQua = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($ketQua);
} catch (Throwable $e) {
    // Trả về mảng rỗng nếu có lỗi phát sinh
    echo json_encode([]);
}