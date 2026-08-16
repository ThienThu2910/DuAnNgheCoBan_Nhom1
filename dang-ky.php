<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/database.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: /DuAnNgheCoBan_Nhom1/admin/index.php');
    exit;
}

if (!empty($_SESSION['user_id'])) {
    header('Location: /DuAnNgheCoBan_Nhom1/tai-khoan.php');
    exit;
}

$loi = [];
$tenDangNhap = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDangNhap = trim($_POST['ten_dang_nhap'] ?? '');
    $matKhau = $_POST['mat_khau'] ?? '';
    $xacNhanMatKhau = $_POST['xac_nhan_mat_khau'] ?? '';

    if ($tenDangNhap === '') {
        $loi[] = 'Vui lòng nhập tên đăng nhập.';
    } elseif (mb_strlen($tenDangNhap) < 3) {
        $loi[] = 'Tên đăng nhập phải có ít nhất 3 ký tự.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $tenDangNhap)) {
        $loi[] = 'Tên đăng nhập chỉ được chứa chữ, số và dấu gạch dưới.';
    }

    if ($matKhau === '') {
        $loi[] = 'Vui lòng nhập mật khẩu.';
    } elseif (strlen($matKhau) < 3) {
        $loi[] = 'Mật khẩu phải có ít nhất 3 ký tự.';
    }

    if ($xacNhanMatKhau === '') {
        $loi[] = 'Vui lòng xác nhận mật khẩu.';
    } elseif ($matKhau !== $xacNhanMatKhau) {
        $loi[] = 'Mật khẩu xác nhận không khớp.';
    }

    /*
     * Kiểm tra tên đăng nhập trong bảng người dùng
     */
    if (empty($loi)) {
        $kiemTraUser = $pdo->prepare(
            'SELECT id
             FROM nguoi_dung
             WHERE ten_dang_nhap = :ten_dang_nhap
             LIMIT 1'
        );

        $kiemTraUser->execute([
            'ten_dang_nhap' => $tenDangNhap
        ]);

        if ($kiemTraUser->fetch()) {
            $loi[] = 'Tên đăng nhập đã tồn tại.';
        }
    }

    /*
     * Không cho đăng ký trùng tài khoản quản trị, ví dụ admin
     */
    if (empty($loi)) {
        $kiemTraAdmin = $pdo->prepare(
            'SELECT id
             FROM admins
             WHERE ten_dang_nhap = :ten_dang_nhap
             LIMIT 1'
        );

        $kiemTraAdmin->execute([
            'ten_dang_nhap' => $tenDangNhap
        ]);

        if ($kiemTraAdmin->fetch()) {
            $loi[] = 'Tên đăng nhập đã được sử dụng.';
        }
    }

    if (empty($loi)) {
        $matKhauMaHoa = password_hash(
            $matKhau,
            PASSWORD_DEFAULT
        );

        $themNguoiDung = $pdo->prepare(
            'INSERT INTO nguoi_dung
                (
                    ho_ten,
                    ten_dang_nhap,
                    email,
                    mat_khau,
                    trang_thai
                )
             VALUES
                (
                    :ho_ten,
                    :ten_dang_nhap,
                    NULL,
                    :mat_khau,
                    1
                )'
        );

        $themNguoiDung->execute([
            'ho_ten' => $tenDangNhap,
            'ten_dang_nhap' => $tenDangNhap,
            'mat_khau' => $matKhauMaHoa
        ]);

        $_SESSION['success'] =
            'Đăng ký tài khoản thành công. Vui lòng đăng nhập.';

        header('Location: /DuAnNgheCoBan_Nhom1/login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Đăng ký tài khoản</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center py-5">
            <div class="col-md-7 col-lg-5">
                <div class="card border-0 shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center text-success mb-4">
                            Đăng ký tài khoản
                        </h2>

                        <?php if (!empty($loi)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($loi as $noiDungLoi): ?>
                                        <li>
                                            <?= htmlspecialchars($noiDungLoi) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="mb-3">
                                <label
                                    for="ten_dang_nhap"
                                    class="form-label"
                                >
                                    Tên đăng nhập
                                </label>

                                <input
                                    type="text"
                                    id="ten_dang_nhap"
                                    name="ten_dang_nhap"
                                    class="form-control"
                                    value="<?= htmlspecialchars($tenDangNhap) ?>"
                                    autocomplete="username"
                                    required
                                    autofocus
                                >
                            </div>

                            <div class="mb-3">
                                <label
                                    for="mat_khau"
                                    class="form-label"
                                >
                                    Mật khẩu
                                </label>

                                <input
                                    type="password"
                                    id="mat_khau"
                                    name="mat_khau"
                                    class="form-control"
                                    autocomplete="new-password"
                                    required
                                >
                            </div>

                            <div class="mb-4">
                                <label
                                    for="xac_nhan_mat_khau"
                                    class="form-label"
                                >
                                    Xác nhận mật khẩu
                                </label>

                                <input
                                    type="password"
                                    id="xac_nhan_mat_khau"
                                    name="xac_nhan_mat_khau"
                                    class="form-control"
                                    autocomplete="new-password"
                                    required
                                >
                            </div>

                            <button
                                type="submit"
                                class="btn btn-success w-100"
                            >
                                Đăng ký
                            </button>
                        </form>

                        <div class="text-center mt-3">
                            <span>Đã có tài khoản?</span>

                            <a href="/DuAnNgheCoBan_Nhom1/login.php">
                                Đăng nhập
                            </a>
                        </div>

                        <div class="text-center mt-3">
                            <a href="/DuAnNgheCoBan_Nhom1/">
                                Quay về trang chủ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>