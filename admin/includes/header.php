<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$adminPage = $adminPage ?? 'tong-quan';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Quản trị hệ thống') ?> - Đặc sản Cà Mau</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --topbar-height: 64px;
            --admin-primary: #641f25;
            --admin-primary-dark: #3d171a;
            --admin-primary-hover: #8a3037;
            --admin-bg: #f4f6f9;
            --admin-card-bg: #ffffff;
            --admin-border: rgba(0, 0, 0, 0.08);
        }

        body {
            background-color: var(--admin-bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #333333;
        }

        /* SIDEBAR */
        .admin-sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1040;
            background: linear-gradient(180deg, var(--admin-primary-dark) 0%, var(--admin-primary) 100%);
            color: #ffffff;
            overflow-y: auto;
            transition: all 0.3s ease;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar-brand {
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            padding: 0 24px;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #ffffff;
            text-decoration: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .sidebar-menu {
            padding: 18px 12px;
            list-style: none;
            margin: 0;
        }

        .sidebar-menu .menu-header {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.45);
            padding: 12px 14px 6px;
        }

        .sidebar-menu .nav-item {
            margin-bottom: 4px;
        }

        .sidebar-menu .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 16px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .sidebar-menu .nav-link i {
            font-size: 18px;
            width: 22px;
        }

        .sidebar-menu .nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
            transform: translateX(4px);
        }

        .sidebar-menu .nav-link.active {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.22);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* MAIN WRAPPER */
        .admin-main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        /* TOPBAR */
        .admin-topbar {
            height: var(--topbar-height);
            background: #ffffff;
            border-bottom: 1px solid var(--admin-border);
            position: sticky;
            top: 0;
            z-index: 1030;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }

        /* CONTENT */
        .admin-content {
            padding: 28px;
            flex-grow: 1;
        }

        /* CARDS & TABLES */
        .admin-card {
            border: 0;
            border-radius: 12px;
            background: var(--admin-card-bg);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        }

        .table thead th {
            background-color: #f8faf9;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555555;
            border-bottom: 2px solid var(--admin-border);
        }

        /* BUTTONS OVERRIDE */
        .btn-success {
            background-color: var(--admin-primary) !important;
            border-color: var(--admin-primary) !important;
        }
        .btn-success:hover {
            background-color: var(--admin-primary-hover) !important;
            border-color: var(--admin-primary-hover) !important;
        }

        @media (max-width: 991.98px) {
            .admin-sidebar {
                left: calc(-1 * var(--sidebar-width));
            }
            .admin-sidebar.show-sidebar {
                left: 0;
            }
            .admin-main-wrapper {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="admin-sidebar" id="adminSidebar">
    <a href="/DuAnNgheCoBan_Nhom1/admin/index.php" class="sidebar-brand">
        <i class="bi bi-shield-lock-fill me-2 fs-5"></i> CÀ MAU ADMIN
    </a>

    <ul class="sidebar-menu">
        <li class="menu-header">Bảng điều khiển</li>
        <li class="nav-item">
            <a href="/DuAnNgheCoBan_Nhom1/admin/index.php" class="nav-link <?= $adminPage === 'tong-quan' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i> Tổng quan
            </a>
        </li>

        <li class="menu-header">Quản lý nội dung</li>
        <li class="nav-item">
            <a href="/DuAnNgheCoBan_Nhom1/admin/dac-san/" class="nav-link <?= $adminPage === 'dac-san' ? 'active' : '' ?>">
                <i class="bi bi-basket2-fill"></i> Đặc sản
            </a>
        </li>
        <li class="nav-item">
            <a href="/DuAnNgheCoBan_Nhom1/admin/danh-muc/" class="nav-link <?= $adminPage === 'danh-muc' ? 'active' : '' ?>">
                <i class="bi bi-tags-fill"></i> Danh mục
            </a>
        </li>
        <li class="nav-item">
            <a href="/DuAnNgheCoBan_Nhom1/admin/co-so/" class="nav-link <?= $adminPage === 'co-so' ? 'active' : '' ?>">
                <i class="bi bi-geo-alt-fill"></i> Cơ sở sản xuất
            </a>
        </li>
        <li class="nav-item">
            <a href="/DuAnNgheCoBan_Nhom1/admin/bai-viet/" class="nav-link <?= $adminPage === 'bai-viet' ? 'active' : '' ?>">
                <i class="bi bi-journal-text"></i> Bài viết văn hóa
            </a>
        </li>
        <li class="nav-item">
            <a href="/DuAnNgheCoBan_Nhom1/admin/lien-he/" class="nav-link <?= $adminPage === 'lien-he' ? 'active' : '' ?>">
                <i class="bi bi-envelope-fill"></i> Liên hệ / Phản hồi
            </a>
        </li>

        <li class="menu-header">Hệ thống</li>
        <li class="nav-item">
            <a href="/DuAnNgheCoBan_Nhom1/index.php" target="_blank" class="nav-link">
                <i class="bi bi-box-arrow-up-right"></i> Xem trang chủ
            </a>
        </li>
        <li class="nav-item">
            <a href="/DuAnNgheCoBan_Nhom1/logout.php" class="nav-link text-danger-emphasis">
                <i class="bi bi-box-arrow-left"></i> Đăng xuất
            </a>
        </li>
    </ul>
</aside>

<!-- MAIN CONTENT WRAPPER -->
<div class="admin-main-wrapper">
    <!-- TOPBAR -->
    <header class="admin-topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-secondary d-lg-none" id="toggleSidebarBtn" type="button">
                <i class="bi bi-list fs-5"></i>
            </button>
            <span class="fw-semibold text-secondary d-none d-md-inline">
                Hệ thống quản lý thông tin đặc sản Cà Mau
            </span>
        </div>

        <div class="d-flex align-items-center gap-3">
            <span class="small fw-semibold text-dark">
                <i class="bi bi-person-circle fs-5 me-1 text-secondary align-middle"></i>
                <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Quản trị viên') ?>
            </span>
            <a href="/DuAnNgheCoBan_Nhom1/logout.php" class="btn btn-sm btn-outline-danger px-3">
                Đăng xuất
            </a>
        </div>
    </header>

    <!-- CONTENT -->
    <main class="admin-content">