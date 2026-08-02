<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$loi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDangNhap = trim($_POST['ten_dang_nhap'] ?? '');
    $matKhau = $_POST['mat_khau'] ?? '';

    if ($tenDangNhap === '' || $matKhau === '') {
        $loi = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, ho_ten, ten_dang_nhap, mat_khau
             FROM admins
             WHERE ten_dang_nhap = :ten_dang_nhap
               AND trang_thai = 1
             LIMIT 1'
        );

        $stmt->execute([
            'ten_dang_nhap' => $tenDangNhap
        ]);

        $admin = $stmt->fetch();

        if ($admin && password_verify($matKhau, $admin['mat_khau'])) {
            session_regenerate_id(true);

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['ho_ten'];
            $_SESSION['admin_username'] = $admin['ten_dang_nhap'];

            header('Location: index.php');
            exit;
        }

        $loi = 'Tên đăng nhập hoặc mật khẩu không chính xác.';
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

    <title>Đăng nhập quản trị</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5 col-lg-4">
                <div class="card border-0 shadow">
                    <div class="card-body p-4">
                        <h3 class="text-center text-success mb-4">
                            Đăng nhập quản trị
                        </h3>

                        <?php if ($loi !== ''): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($loi) ?>
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

                        <a
                            href="/dac-san-ca-mau/"
                            class="btn btn-link w-100 mt-2"
                        >
                            Quay về trang chủ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>