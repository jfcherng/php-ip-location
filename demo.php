<?php

use Jfcherng\Ip\IpLocation;

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
