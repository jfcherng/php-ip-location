# php-ip-location

利用 `IPIP` 和 `cz88 （純真）` 兩個資料庫來查詢 IP 的地理位置。


## 安裝流程

1. 使用 Composer 安裝： `composer require jfcherng/php-ip-location --no-dev`

1. 取得 UTF-8 編碼的 `17monipdb.datx` IP 資料庫

   - 從 https://www.ipip.net/download.html 下載免費版離線資料庫

1. 取得 GB2312 編碼的 `qqwry.dat` IP 資料庫

   - 從 http://update.cz88.net/soft/setup.zip 下載後解壓縮得到
   
   如果需要繁體化並改為 UTF-8 編碼：
   
   1. 使用 `IPLook.exe` 將 `qqwry.dat` 轉換為 txt 格式
   1. 使用任意工具將 txt 轉換為 UTF-8 （有需要的話也可以自己轉繁體）
   1. 使用 `IPLook.exe` 將 txt 轉換回 dat 格式

1. 於使用時設定兩個資料庫的路徑


## 使用方式

見 `demo.php`

```php
<?php

use Jfcherng\IpLocation\IpLocation;

include __DIR__ . '/vendor/autoload.php';

// one time setup for the IpLocation class
IpLocation::setup([
    // the ipip DB file location
    'ipipDb' => __DIR__ . '/src/db/17monipdb.datx',
    // the cz88 DB file location
    'cz88Db' => __DIR__ . '/src/db/qqwry.dat',
    // whether the cz88 DB is UTF-8 encoded? (typically not)
    'cz88DbIsUtf8' => false,
]);

$ip = '202.113.245.255';

$results = IpLocation::lookup($ip);

// array(4) {
//    [0] => string(6)  "中国"
//    [1] => string(6)  "天津"
//    [2] => string(6)  "天津"
//    [3] => string(24) "天津工程师范学院"
// }.
var_dump($results);
```


Supporters <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ATXYY9Y78EQ3Y" target="_blank"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" /></a>
==========

Thank you guys for sending me some cups of coffee.
