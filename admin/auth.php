<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['admin_id'])) {
    header('Location: /DuAnNgheCoBan_Nhom1/admin/login.php');
    exit;
}