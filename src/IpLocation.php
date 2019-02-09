<?php

declare(strict_types=1);

namespace Jfcherng\IpLocation;

use Exception;

/**
 * Class for looking up IP location information.
 *
 * @author Jack Cherng <jfcherng@gmail.com>
 */
class IpLocation
{
    // indexes of array properties for cz88 DB
    const CZ88_COUNTRY = 0;
    const CZ88_ISP = 1;

    // indexes of array properties for ipip DB
    const IPIP_COUNTRY = 0;
    const IPIP_PROVINCE = 1;
    const IPIP_COUNTY = 2;
    const IPIP_ISP = 3;

    /**
     * The lookup results cache.
     *
     * @var array
     */
    protected static $cache = [];

    /**
     * The cz88 DB file location.
     *
     * @var string
     */
    protected static $cz88Db = __DIR__ . '/db/qqwry.dat';

    /**
     * Is the cz88 DB file UTF-8 encoded?
     *
     * @var bool true / false = UTF-8 / gb2312
     */
    protected static $cz88DbIsUtf8 = false;

    /**
     * The ipip DB file location.
     *
     * @var string
     */
    protected static $ipipDb = __DIR__ . '/db/17monipdb.datx';

    /**
     * The file handler of ipip DB file.
     *
     * @var null|false|resource
     */
    protected static $ipipFp = null;
    protected static $ipipIndex = null;
    protected static $ipipOffset = null;

    /**
     * Not allowing instantiation. Just use static methods.
     */
    protected function __construct()
    {
    }

    /**
     * Set static properties for this class.
     *
     * @param array $options The options
     */
    public static function setup(array $options = []): void
    {
        if (isset($options['cz88Db'])) {
            static::$cz88Db = $options['cz88Db'];
        }

        if (isset($options['cz88DbIsUtf8'])) {
            static::$cz88DbIsUtf8 = $options['cz88DbIsUtf8'];
        }

        if (isset($options['ipipDb'])) {
            static::$ipipDb = $options['ipipDb'];
        }

        // let ipipDb get reloaded
        if (isset(static::$ipipFp)) {
            \fclose(static::$ipipFp);
            static::$ipipFp = null;
        }
    }

    /**
     * Look up IP location information.
     *
     * @param string $ip the IP string
     *
     * @return array the IP location results
     */
    public static function lookup(string $ip): array
    {
        $ip = \gethostbyname($ip);

        if (isset(static::$cache[$ip])) {
            return static::$cache[$ip];
        }

        $resultCz88 = static::lookupCz88($ip);
        $resultIpip = static::lookupIpip($ip);

        // the primary result
        $result = $resultIpip;

        if (empty($result) || \count($result) < 4 || $result[0] === 'N/A') {
            return [];
        }

        if ($result[static::IPIP_COUNTRY] === $result[static::IPIP_PROVINCE]) {
            $result[static::IPIP_PROVINCE] = '';
        }

        // utilize results from cz88 DB as well
        if ($resultCz88[0] !== '-') {
            $resultCz88 = \explode("\t", $resultCz88, 2);

            if ($resultCz88[static::CZ88_ISP] !== '') {
                $result[static::IPIP_ISP] = $resultCz88[static::CZ88_ISP];
            }
        }

        static::$cache[$ip] = $result;

        return $result;
    }

    /**
     * Look up IP location information from cz88 DB.
     *
     * @param string $ip the IP string
     *
     * @throws Exception invalid db file
     *
     * @return string the IP location results
     */
    protected static function lookupCz88(string $ip): string
    {
        if (!$fd = \fopen(static::$cz88Db, 'r')) {
            throw new Exception('Invalid qqwry.dat file!');
        }

        $ip = \explode('.', $ip);
        $ipNum = $ip[0] * 16777216 + $ip[1] * 65536 + $ip[2] * 256 + $ip[3];

        if (!($dataBegin = \fread($fd, 4)) || !($dataEnd = \fread($fd, 4))) {
            return "-\tSystem Error";
        }

        $ipbegin = (int) \implode('', \unpack('L', $dataBegin));
        if ($ipbegin < 0) {
            $ipbegin += 2 ** 32;
        }

        $ipend = (int) \implode('', \unpack('L', $dataEnd));
        if ($ipend < 0) {
            $ipend += 2 ** 32;
        }

        $ipAllNum = ($ipend - $ipbegin) / 7 + 1;
        $beginNum = $ip2num = $ip1num = 0;
        $ipAddr1 = $ipAddr2 = '';
        $endNum = $ipAllNum;

        while ($ip1num > $ipNum || $ip2num < $ipNum) {
            $middle = (int) (($endNum + $beginNum) / 2);
            \fseek($fd, $ipbegin + 7 * $middle);

            $ipData1 = \fread($fd, 4);
            if (\strlen($ipData1) < 4) {
                \fclose($fd);

                return "-\tSystem Error";
            }

            $ip1num = (int) \implode('', \unpack('L', $ipData1));
            if ($ip1num < 0) {
                $ip1num += 2 ** 32;
            }

            if ($ip1num > $ipNum) {
                $endNum = $middle;

                continue;
            }

            $dataSeek = \fread($fd, 3);
            if (\strlen($dataSeek) < 3) {
                \fclose($fd);

                return "-\tSystem Error";
            }

            $dataSeek = (int) \implode('', \unpack('L', $dataSeek . \chr(0)));
            \fseek($fd, $dataSeek);

            $ipData2 = \fread($fd, 4);
            if (\strlen($ipData2) < 4) {
                \fclose($fd);

                return "-\tSystem Error";
            }

            $ip2num = (int) \implode('', \unpack('L', $ipData2));
            if ($ip2num < 0) {
                $ip2num += 2 ** 32;
            }

            if ($ip2num < $ipNum) {
                if ($middle === $beginNum) {
                    \fclose($fd);

                    return "-\tUnknown";
                }

                $beginNum = $middle;
            }
        }

        $ipFlag = \fread($fd, 1);
        if ($ipFlag === \chr(1)) {
            $ipSeek = \fread($fd, 3);
            if (\strlen($ipSeek) < 3) {
                \fclose($fd);

                return "-\tSystem Error";
            }

            $ipSeek = (int) \implode('', \unpack('L', $ipSeek . \chr(0)));
            \fseek($fd, $ipSeek);
            $ipFlag = \fread($fd, 1);
        }

        if ($ipFlag === \chr(2)) {
            $addrSeek = \fread($fd, 3);
            if (\strlen($addrSeek) < 3) {
                \fclose($fd);

                return "-\tSystem Error";
            }

            $ipFlag = \fread($fd, 1);
            if ($ipFlag === \chr(2)) {
                $addrSeek2 = \fread($fd, 3);
                if (\strlen($addrSeek2) < 3) {
                    \fclose($fd);

                    return "-\tSystem Error";
                }

                $addrSeek2 = (int) \implode('', \unpack('L', $addrSeek2 . \chr(0)));
                \fseek($fd, $addrSeek2);
            } else {
                \fseek($fd, -1, \SEEK_CUR);
            }

            while (($char = \fread($fd, 1)) !== \chr(0)) {
                $ipAddr2 .= $char;
            }

            $addrSeek = (int) \implode('', \unpack('L', $addrSeek . \chr(0)));
            \fseek($fd, $addrSeek);

            while (($char = \fread($fd, 1)) !== \chr(0)) {
                $ipAddr1 .= $char;
            }
        } else {
            \fseek($fd, -1, \SEEK_CUR);
            while (($char = \fread($fd, 1)) !== \chr(0)) {
                $ipAddr1 .= $char;
            }

            $ipFlag = \fread($fd, 1);
            if ($ipFlag === \chr(2)) {
                $addrSeek2 = \fread($fd, 3);
                if (\strlen($addrSeek2) < 3) {
                    \fclose($fd);

                    return "-\tSystem Error";
                }

                $addrSeek2 = (int) \implode('', \unpack('L', $addrSeek2 . \chr(0)));
                \fseek($fd, $addrSeek2);
            } else {
                \fseek($fd, -1, \SEEK_CUR);
            }

            while (($char = \fread($fd, 1)) !== \chr(0)) {
                $ipAddr2 .= $char;
            }
        }

        \fclose($fd);

        $ipaddr = \str_replace('CZ88.NET', '', "{$ipAddr1}\t{$ipAddr2}");
        if (\strpos($ipaddr, 'http:') !== false || $ipaddr === '') {
            $ipaddr = "-\tUnknown";
        }

        return static::$cz88DbIsUtf8 ? $ipaddr : \iconv('gb2312', 'utf-8', $ipaddr);
    }

    /**
     * Look up IP location information from ipip DB.
     *
     * @see https://github.com/ipipdotnet/datx-php
     *
     * @param string $ip the IP string
     *
     * @throws Exception invalid db file
     *
     * @return array the IP location results
     */
    protected static function lookupIpip(string $ip): array
    {
        // init
        if (!isset(static::$ipipFp)) {
            static::$ipipFp = \fopen(static::$ipipDb, 'r');
            if (static::$ipipFp === false) {
                throw new Exception('Invalid 17monipdb.datx file!');
            }

            static::$ipipOffset = \unpack('Nlen', \fread(static::$ipipFp, 4));
            if (static::$ipipOffset['len'] < 4) {
                throw new Exception('Invalid 17monipdb.datx file!');
            }

            static::$ipipIndex = \fread(static::$ipipFp, static::$ipipOffset['len'] - 4);
        }

        if (empty($ip)) {
            return ['N/A'];
        }

        $nip = \gethostbyname($ip);
        $ipdot = \explode('.', $ip);

        if ($ipdot[0] < 0 || $ipdot[0] > 255 || \count($ipdot) !== 4) {
            return ['N/A'];
        }

        $nip2 = \pack('N', \ip2long($nip));

        $tmpOffset = ((int) $ipdot[0] * 256 + (int) $ipdot[1]) * 4;
        $start = \unpack('Vlen', static::$ipipIndex[$tmpOffset] . static::$ipipIndex[$tmpOffset + 1] . static::$ipipIndex[$tmpOffset + 2] . static::$ipipIndex[$tmpOffset + 3]);

        $indexOffset = $indexLength = null;
        $maxCompLen = static::$ipipOffset['len'] - 262144 - 4;
        for ($start = $start['len'] * 9 + 262144; $start < $maxCompLen; $start += 9) {
            if (static::$ipipIndex[$start] . static::$ipipIndex[$start + 1] . static::$ipipIndex[$start + 2] . static::$ipipIndex[$start + 3] >= $nip2) {
                $indexOffset = \unpack('Vlen', static::$ipipIndex[$start + 4] . static::$ipipIndex[$start + 5] . static::$ipipIndex[$start + 6] . "\x0");
                $indexLength = \unpack('nlen', static::$ipipIndex[$start + 7] . static::$ipipIndex[$start + 8]);

                break;
            }
        }

        if (!isset($indexOffset)) {
            return ['N/A'];
        }

        \fseek(static::$ipipFp, static::$ipipOffset['len'] + $indexOffset['len'] - 262144);

        return \explode("\t", \fread(static::$ipipFp, $indexLength['len']));
    }
}
