<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Đặc sản Cà Mau';
$currentPage = $currentPage ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="/dac-san-ca-mau/assets/css/style.css"
    >
</head>
<body>