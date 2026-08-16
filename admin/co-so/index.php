<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';    
require_once __DIR__ . '/../../config/database.php';  
require_once __DIR__ . '/../../includes/functions.php';

$thongBao = $_SESSION['success'] ?? '';    
$loi = $_SESSION['error'] ?? '';    
unset($_SESSION['success'], $_SESSION['error']);

$tuKhoa = trim($_GET['q'] ?? '');

$sql = '    
    SELECT cs.*,    
        (SELECT COUNT(*) FROM dac_san_co_so AS dscs WHERE dscs.co_so_id = cs.id) AS so_dac_san    
    FROM co_so_san_xuat AS cs    
    WHERE 1 = 1    
';    
$params = [];

if ($tuKhoa !== '') {    
    $sql .= ' AND (cs.ten_co_so LIKE :tu_khoa OR cs.dia_chi LIKE :tu_khoa)';    
    $params['tu_khoa'] = '%' . $tuKhoa . '%';    
}

$sql .= ' ORDER BY cs.id DESC';    
$stmt = $pdo->prepare($sql);    
$stmt->execute($params);    
$danhSachCoSo = $stmt->fetchAll();    
?>
<!DOCTYPE html>    
<html lang="vi">    
<head>    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    
    <title>Quản lý cơ sở sản xuất</title>    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">    
    <style>    
        .facility-image { width: 95px; height: 70px; object-fit: cover; border-radius: 8px; }    
        .no-image { width: 95px; height: 70px; display: flex; align-items: center; justify-content: center; border-radius: 8px; color: #6c757d; background-color: #e9ecef; font-size: 13px; }    
    </style>    
</head>  

<body class="bg-light">    
    <nav class="navbar navbar-dark bg-success">    
        <div class="container">    
            <a class="navbar-brand fw-bold" href="/DuAnNgheCoBan_Nhom1/admin/index.php">Quản trị đặc sản Cà Mau</a>    
            <div>
                <span class="text-white me-3"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>    
                <a href="/DuAnNgheCoBan_Nhom1/logout.php" class="btn btn-outline-light btn-sm">Đăng xuất</a>    
            </div>    
        </div>    
    </nav>  

    <main class="container py-4">    
        <div class="d-flex justify-content-between align-items-center mb-4">    
            <div>    
                <h1 class="h3 mb-1">Quản lý cơ sở sản xuất</h1>    
                <p class="text-muted mb-0">Quản lý địa chỉ và vị trí giới thiệu đặc sản.</p>    
            </div>    
            <a href="create.php" class="btn btn-success">+ Thêm cơ sở</a>    
        </div>  

        <?php if ($thongBao !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($thongBao) ?></div><?php endif; ?>    
        <?php if ($loi !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($loi) ?></div><?php endif; ?>  

        <div class="card border-0 shadow-sm mb-4">    
            <div class="card-body">    
                <form method="get" class="row g-3">    
                    <div class="col-md-9">    
                        <input type="text" id="q" name="q" class="form-control" placeholder="Nhập tên cơ sở hoặc địa chỉ..." value="<?= htmlspecialchars($tuKhoa) ?>">    
                    </div>    
                    <div class="col-md-3 d-flex gap-2">    
                        <button type="submit" class="btn btn-success flex-grow-1">Tìm kiếm</button>    
                        <a href="index.php" class="btn btn-secondary">Xóa lọc</a>    
                    </div>    
                </form>    
            </div>    
        </div>  

        <div class="card border-0 shadow-sm">    
            <div class="card-body p-0">    
                <div class="table-responsive">    
                    <table class="table table-bordered table-hover align-middle mb-0">    
                        <thead class="table-success">    
                            <tr>    
                                <th style="width: 65px;">STT</th>    
                                <th style="width: 120px;">Hình ảnh</th>    
                                <th>Tên cơ sở</th>    
                                <th>Địa chỉ</th>    
                                <th style="width: 110px;">Đặc sản</th>    
                                <th style="width: 120px;">Trạng thái</th>    
                                <th style="width: 220px;">Thao tác</th>    
                            </tr>    
                        </thead>    
                        <tbody>    
                            <?php if (empty($danhSachCoSo)): ?><!---->
                                <tr><td colspan="7" class="text-center text-muted py-4">Chưa có cơ sở sản xuất nào.</td></tr>    
                            <?php else: ?><!---->
                                <?php foreach ($danhSachCoSo as $index => $coSo): ?><!---->
                                    <tr>    
                                        <td><?= $index + 1 ?></td>    
                                        <td>    
                                            <?php if (!empty($coSo['hinh_anh'])): ?><!---->
                                                <img src="/DuAnNgheCoBan_Nhom1/assets/uploads/co-so/<?= htmlspecialchars($coSo['hinh_anh']) ?>" class="facility-image">    
                                            <?php else: ?><!---->
                                                <div class="no-image">Chưa có ảnh</div>    
                                            <?php endif; ?>    
                                        </td>    
                                        <td>    
                                            <strong><?= htmlspecialchars($coSo['ten_co_so']) ?></strong>    
                                            <?php if (!empty($coSo['so_dien_thoai'])): ?><!---->
                                                <div class="small text-muted mt-1"><?= htmlspecialchars($coSo['so_dien_thoai']) ?></div>    
                                            <?php endif; ?>    
                                        </td>    
                                        <td><?= htmlspecialchars($coSo['dia_chi']) ?></td>    
                                        <td class="text-center"><span class="badge bg-info text-dark"><?= (int) $coSo['so_dac_san'] ?></span></td>    
                                        <td>    
                                            <span class="badge <?= (int)$coSo['trang_thai'] === 1 ? 'bg-success' : 'bg-secondary' ?>">  
                                                <?= (int)$coSo['trang_thai'] === 1 ? 'Hiển thị' : 'Đang ẩn' ?>  
                                            </span>  
                                        </td>    
                                        <td>    
                                            <a href="<?= htmlspecialchars(taoGoogleMapsUrl($coSo)) ?>" target="_blank" class="btn btn-primary btn-sm">Bản đồ</a>    
                                            <a href="edit.php?id=<?= (int)$coSo['id'] ?>" class="btn btn-warning btn-sm">Sửa</a>    
                                            <button type="button" class="btn btn-danger btn-sm" onclick="xacNhanXoa(<?= (int)$coSo['id'] ?>, '<?= htmlspecialchars($coSo['ten_co_so'], ENT_QUOTES) ?>')">  
                                                Xóa  
                                            </button>  
                                        </td>    
                                    </tr>    
                                <?php endforeach; ?>    
                            <?php endif; ?>    
                        </tbody>    
                    </table>    
                </div>    
            </div>    
        </div>    
        <a href="/DuAnNgheCoBan_Nhom1/admin/index.php" class="btn btn-secondary mt-4">Quay lại trang quản trị</a>    
    </main>  

    <!-- Modal Xác Nhận Xóa -->  
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">  
        <div class="modal-dialog modal-dialog-centered">  
            <div class="modal-content">  
                <div class="modal-header bg-danger text-white">  
                    <h5 class="modal-title">Xác nhận xóa</h5>  
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>  
                </div>  
                <div class="modal-body">  
                    Bạn có chắc chắn muốn xóa cơ sở <strong id="tenCoSoXoa"></strong> không? Thao tác này sẽ đồng thời xóa liên kết của cơ sở này với các đặc sản.  
                </div>  
                <div class="modal-footer">  
                    <form method="post" action="delete.php">  
                        <input type="hidden" name="id" id="idCoSoXoa" value="">  
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>  
                        <button type="submit" class="btn btn-danger">Xóa ngay</button>  
                    </form>  
                </div>  
            </div>  
        </div>  
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>  
    <script>  
    function xacNhanXoa(id, ten) {  
        document.getElementById('idCoSoXoa').value = id;  
        document.getElementById('tenCoSoXoa').innerText = ten;  
        var modal = new bootstrap.Modal(document.getElementById('deleteModal'));  
        modal.show();  
    }  
    </script>  
</body>    
</html>