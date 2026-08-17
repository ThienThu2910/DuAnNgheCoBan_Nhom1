<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Đặc sản Cà Mau';
$currentPage = $currentPage ?? '';

if (!isset($baseUrl)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $baseUrl = preg_replace('#/(admin|api|includes).*$#', '', $scriptDir);
    $baseUrl = rtrim($baseUrl, '/');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Khám phá đặc sản, cơ sở sản xuất và câu chuyện văn hóa Cà Mau.">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <script>
        (function () {
            try {
                var theme = localStorage.getItem('cm-theme');
                if (theme !== 'dark' && theme !== 'light') {
                    theme = 'light';
                }
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/style.css?v=20260817">
</head>
<body>