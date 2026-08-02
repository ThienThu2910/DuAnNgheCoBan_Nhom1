<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/database.php';

// Nếu đã đăng nhập thì chuyển đến trang tương ứng
if (!empty($_SESSION['admin_id'])) {
    header('Location: /dac-san-ca-mau/admin/index.php');
    exit;
}

if (!empty($_SESSION['user_id'])) {
    header('Location: /dac-san-ca-mau/');
    exit;
}

$loi = '';
$khongCoTaiKhoan = false;

$thongBao = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDangNhap = trim($_POST['ten_dang_nhap'] ?? '');
    $matKhau = $_POST['mat_khau'] ?? '';

    if ($tenDangNhap === '' || $matKhau === '') {
        $loi = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        /*
         * Bước 1: Kiểm tra tài khoản quản trị
         */
        $stmtAdmin = $pdo->prepare(
            'SELECT id, ho_ten, ten_dang_nhap, mat_khau
             FROM admins
             WHERE ten_dang_nhap = :ten_dang_nhap
               AND trang_thai = 1
             LIMIT 1'
        );

        $stmtAdmin->execute([
            'ten_dang_nhap' => $tenDangNhap
        ]);

        $admin = $stmtAdmin->fetch();

        if ($admin) {
            if (password_verify($matKhau, $admin['mat_khau'])) {
                session_regenerate_id(true);

                $_SESSION['admin_id'] = (int) $admin['id'];
                $_SESSION['admin_name'] = $admin['ho_ten'];
                $_SESSION['admin_username'] = $admin['ten_dang_nhap'];
                $_SESSION['role'] = 'admin';

                header('Location: /dac-san-ca-mau/admin/index.php');
                exit;
            }

            $loi = 'Mật khẩu không chính xác.';
        } else {
            /*
             * Bước 2: Kiểm tra tài khoản người dùng
             */
            $stmtUser = $pdo->prepare(
                'SELECT id, ho_ten, ten_dang_nhap, mat_khau
                 FROM nguoi_dung
                 WHERE ten_dang_nhap = :ten_dang_nhap
                   AND trang_thai = 1
                 LIMIT 1'
            );

            $stmtUser->execute([
                'ten_dang_nhap' => $tenDangNhap
            ]);

            $user = $stmtUser->fetch();

            if ($user) {
                if (password_verify($matKhau, $user['mat_khau'])) {
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = (int) $user['id'];
                    $_SESSION['user_name'] = $user['ho_ten'];
                    $_SESSION['user_username'] = $user['ten_dang_nhap'];
                    $_SESSION['role'] = 'user';

                    header('Location: /dac-san-ca-mau/');
                    exit;
                }

                $loi = 'Mật khẩu không chính xác.';
            } else {
                $loi = 'Tài khoản chưa tồn tại. Vui lòng đăng ký tài khoản.';
                $khongCoTaiKhoan = true;
            }
        }
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

    <title>Đăng nhập</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center text-success mb-4">
                            Đăng nhập
                        </h2>

                        <?php if ($thongBao !== ''): ?>
                            <div class="alert alert-success">
                                <?= htmlspecialchars($thongBao) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($loi !== ''): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($loi) ?>

                                <?php if ($khongCoTaiKhoan): ?>
                                    <div class="mt-2">
                                        <a
                                            href="/dac-san-ca-mau/dang-ky.php"
                                            class="alert-link"
                                        >
                                            Đăng ký tài khoản ngay
                                        </a>
                                    </div>
                                <?php endif; ?>
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
                                    value="<?= htmlspecialchars(
                                        $_POST['ten_dang_nhap'] ?? ''
                                    ) ?>"
                                    required
                                    autofocus
                                >
                            </div>

                            <div class="mb-4">
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
                                    required
                                >
                            </div>

                            <button
                                type="submit"
                                class="btn btn-success w-100"
                            >
                                Đăng nhập
                            </button>
                        </form>

                        <div class="text-center mt-3">
                            <span>Chưa có tài khoản?</span>

                            <a href="/dac-san-ca-mau/dang-ky.php">
                                Đăng ký
                            </a>
                        </div>

                        <div class="text-center mt-3">
                            <a href="/dac-san-ca-mau/">
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