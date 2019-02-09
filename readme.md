# php-ip-location

利用 `IPIP` 和 `cz88 （純真）` 兩個資料庫來查詢 IP 的地理位置。


## 安裝流程

1. 使用 Composer 安裝： `composer require jfcherng/php-ip-location`

1. 這樣就可以了，但如果你想要自己更新 IP 資料庫，請參考以下步驟：

   1. 取得 UTF-8 編碼的 `ipipfree.ipdb` IP 資料庫

      - 從 https://www.ipip.net/download.html 下載免費版離線資料庫
        （需要登入，可以免費註冊帳號）

   1. 取得 GB2312 編碼的 `qqwry.dat` IP 資料庫

      - 下載 http://update.cz88.net/soft/setup.zip 後解壓縮得到 `setup.exe`
      - 使用 `UniExtract` 或其他工具將 `setup.exe` 解壓縮得到 `qqwry.dat`
      
      如果還需要繁體化或改為 UTF-8 編碼：
      
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
```


Supporters <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ATXYY9Y78EQ3Y" target="_blank"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" /></a>
==========

Thank you guys for sending me some cups of coffee.
