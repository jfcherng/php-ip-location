<?php

use Jfcherng\IpLocation\IpLocation;

include __DIR__ . '/vendor/autoload.php';

// 如果不想要使用內建的 IP 資料庫，請進行以下設定
IpLocation::setup([
    // ipip 資料庫的路徑
    'ipipDb' => __DIR__ . '/src/db/ipipfree.ipdb',
    // cz88 資料庫的路徑
    'cz88Db' => __DIR__ . '/src/db/qqwry.dat',
    // cz88 資料庫是否為 UTF-8 編碼
    'cz88DbIsUtf8' => false,
]);

$ip = '202.113.245.255';

$results = IpLocation::find($ip);

// array(4) {
//    [0] => string(6)  "中国"
//    [1] => string(6)  "天津"
//    [2] => string(6)  "天津"
//    [3] => string(24) "天津工程师范学院教育网"
// }
\var_dump($results);
