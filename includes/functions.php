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
 * Tự động bóc tách Vĩ độ và Kinh độ từ link Google Maps (cả link rút gọn)
 */
function layToaDoTuGoogleMapsUrl(string $url): array
{
    $viDo = null;
    $kinhDo = null;

    if (empty($url)) {
        return ['vi_do' => null, 'kinh_do' => null];
    }

    if (strpos($url, 'goo.gl') !== false || strpos($url, 'maps.app.goo.gl') !== false) {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_exec($ch);
            $fullUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            curl_close($ch);

            if (!empty($fullUrl)) {
                $url = $fullUrl;
            }
        }
    }

    $url = rawurldecode($url);

    if (preg_match('/@(-?[0-9]+\.[0-9]+),(-?[0-9]+\.[0-9]+)/', $url, $matches)) {
        $viDo = (float)$matches[1];
        $kinhDo = (float)$matches[2];
    } elseif (preg_match('/!3d(-?[0-9]+\.[0-9]+)!4d(-?[0-9]+\.[0-9]+)/', $url, $matches)) {
        $viDo = (float)$matches[1];
        $kinhDo = (float)$matches[2];
    } elseif (preg_match('/[?&](?:q|query|center)=(-?[0-9]+\.[0-9]+),(-?[0-9]+\.[0-9]+)/', $url, $matches)) {
        $viDo = (float)$matches[1];
        $kinhDo = (float)$matches[2];
    }

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

    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($coSo['ten_co_so'] . ', ' . $coSo['dia_chi']);
}