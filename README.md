# php-ip-location

[![GitHub Workflow Status (branch)](https://img.shields.io/github/actions/workflow/status/jfcherng/php-ip-location/php.yml?branch=master&style=flat-square)](https://github.com/jfcherng/php-ip-location/actions)
[![Packagist](https://img.shields.io/packagist/dt/jfcherng/php-ip-location?style=flat-square)](https://packagist.org/packages/jfcherng/php-ip-location)
[![Packagist Version](https://img.shields.io/packagist/v/jfcherng/php-ip-location?style=flat-square)](https://packagist.org/packages/jfcherng/php-ip-location)
[![Project license](https://img.shields.io/github/license/jfcherng/php-ip-location?style=flat-square)](https://github.com/jfcherng/php-ip-location/blob/master/LICENSE)
[![GitHub stars](https://img.shields.io/github/stars/jfcherng/php-ip-location?style=flat-square&logo=github)](https://github.com/jfcherng/php-ip-location/stargazers)
[![Donate to this project using Paypal](https://img.shields.io/badge/paypal-donate-blue.svg?style=flat-square&logo=paypal)](https://www.paypal.me/jfcherng/5usd)

利用 `IPIP` 和 `cz88 （純真）` 兩個資料庫來查詢 IP 的地理位置。


## 安裝流程

1. 使用 Composer 安裝： `composer require jfcherng/php-ip-location`

1. 這樣就可以了，但如果你想要自己更新 IP 資料庫，請參考以下步驟：

   1. 取得 IPIP.net 的 IP 離線資料庫 (`ipipfree.ipdb`)

      - 從 https://www.ipip.net/download.html 下載免費版離線資料庫
        （需要登入以及手機驗證，可以免費註冊帳號）

   1. 純真 IP 資料庫 (`qqwry.dat`) 的 IPDB 格式版本
   
      - 從 https://github.com/metowolf/qqwry.ipdb 下載[標準版](https://cdn.jsdelivr.net/npm/qqwry.ipdb/qqwry.ipdb)

   1. 於使用時自行設定兩個資料庫的路徑


## 使用方式

見 `demo.php`

```php
<?php

use Jfcherng\IpLocation\IpLocation;

include __DIR__ . '/vendor/autoload.php';

$ipFinder = IpLocation::getInstance();

// 如果不想要使用內建的 IP 資料庫，請進行以下設定
$ipFinder->setup([
    // ipip 資料庫的路徑
    'ipipDb' => __DIR__ . '/src/db/ipipfree.ipdb',
    // cz88 資料庫的路徑
    'cz88Db' => __DIR__ . '/src/db/qqwry.ipdb',
]);

$ip = '202.113.245.255';

$results = $ipFinder->find($ip);

\var_dump($results);
/*
array(5) {
  ["country_name"]=>
  string(6) "中国"
  ["region_name"]=>
  string(6) "天津"
  ["city_name"]=>
  string(6) "天津"
  ["owner_domain"]=>
  string(0) ""
  ["isp_domain"]=>
  string(9) "教育网"
}
*/
```
