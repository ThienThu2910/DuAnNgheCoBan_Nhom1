<?php

declare(strict_types=1);

/**
 * Hàm tạo Slug chuẩn tiếng Việt
 */
function taoSlug(string $chuoi): string
{
    $chuoi = mb_strtolower(trim($chuoi), 'UTF-8');

    $tim = [
        'á', 'à', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ',
        'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'é', 'è', 'ẻ', 'ẽ', 'ẹ',
        'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'í', 'ì', 'ỉ', 'ĩ', 'ị',
        'ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ',
        'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ú', 'ù', 'ủ', 'ũ', 'ụ',
        'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ', 'đ'
    ];

    $thay = [
        'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
        'a', 'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'e',
        'e', 'e', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'i',
        'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
        'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'u',
        'u', 'u', 'u', 'u', 'u', 'u', 'y', 'y', 'y', 'y', 'y', 'd'
    ];

    $chuoi = str_replace($tim, $thay, $chuoi);
    $chuoi = preg_replace('/[^a-z0-9]+/', '-', $chuoi);

    return trim((string)$chuoi, '-');
}

/**
 * Tự động bóc tách Vĩ độ và Kinh độ từ link Google Maps (xử lý cả link rút gọn app.goo.gl)
 */
function layToaDoTuGoogleMapsUrl(string $url): array
{
    $viDo = null;
    $kinhDo = null;

    if (empty($url)) {
        return ['vi_do' => null, 'kinh_do' => null];
    }

    // Xử lý mở rộng link rút gọn maps.app.goo.gl hoặc goo.gl
    if (strpos($url, 'goo.gl') !== false || strpos($url, 'maps.app.goo.gl') !== false) {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);
            $response = curl_exec($ch);
            $fullUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            curl_close($ch);

            if (!empty($fullUrl) && $fullUrl !== $url) {
                $url = $fullUrl;
            } elseif (!empty($response) && preg_match('/URL=([^\'"\s>]+)/i', $response, $matches)) {
                $url = $matches[1];
            }
        }
    }

    $url = rawurldecode($url);

    // 1. Dạng @vĩ_độ,kinh_độ
    if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
        $viDo = (float)$matches[1];
        $kinhDo = (float)$matches[2];
    } 
    // 2. Dạng !3d vĩ độ !4d kinh độ
    elseif (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $url, $matches)) {
        $viDo = (float)$matches[1];
        $kinhDo = (float)$matches[2];
    }
    // 3. Dạng query hoặc center
    elseif (preg_match('/[?&](?:q|query|center|ll)=(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
        $viDo = (float)$matches[1];
        $kinhDo = (float)$matches[2];
    }

    // Đảm bảo tọa độ nằm trong khoảng thực tế của Việt Nam (Vĩ độ ~8-24, Kinh độ ~102-110)
    if ($viDo !== null && ($viDo < -90 || $viDo > 90)) $viDo = null;
    if ($kinhDo !== null && ($kinhDo < -180 || $kinhDo > 180)) $kinhDo = null;

    return ['vi_do' => $viDo, 'kinh_do' => $kinhDo];
}

/**
 * Tạo URL mở trên trang Google Maps
 */
function taoGoogleMapsUrl(array $coSo): string
{
    if (!empty($coSo['google_maps_url'])) {
        return $coSo['google_maps_url'];
    }

    if (!empty($coSo['vi_do']) && !empty($coSo['kinh_do'])) {
        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($coSo['vi_do'] . ',' . $coSo['kinh_do']);
    }

    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(($coSo['ten_co_so'] ?? '') . ', ' . ($coSo['dia_chi'] ?? ''));
}