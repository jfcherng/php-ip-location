<?php

declare(strict_types=1);

namespace Jfcherng\IpLocation;

use ipip\db\City as ipipCity;

final class IpLocation
{
    // indexes of array properties for our final result
    const IDX_COUNTRY = 0;
    const IDX_PROVINCE = 1;
    const IDX_COUNTY = 2;
    const IDX_ISP = 3;

    // indexes of array properties for cz88 DB
    const CZ88_COUNTRY = 0;
    const CZ88_ISP = 1;

    // indexes of array properties for ipip DB
    const IPIP_COUNTRY = 0;
    const IPIP_PROVINCE = 1;
    const IPIP_COUNTY = 2;

    /**
     * The ipipCity instance.
     *
     * @var \ipip\db\City
     */
    private static $ipipCity;

    /**
     * The options.
     *
     * @var array
     */
    private static $options = [
        // the ipip DB file location
        'ipipDb' => __DIR__ . '/db/ipipfree.ipdb',
        // the cz88 DB file location
        'cz88Db' => __DIR__ . '/db/qqwry.dat',
        // is the cz88 DB file UTF-8 encoded?
        'cz88DbIsUtf8' => false,
    ];

    /**
     * Not allowing instantiation. Just use static methods.
     */
    private function __construct()
    {
    }

    /**
     * Set static properties for this class.
     *
     * @param array $options The options
     */
    public static function setup(array $options): void
    {
        foreach ($options as $key => $value) {
            if (\array_key_exists($key, self::$options)) {
                self::$options[$key] = $value;
            }
        }
    }

    /**
     * Find IP location information.
     *
     * @param string $ip the IP string
     *
     * @return array the IP location results
     */
    public static function find(string $ip): array
    {
        $ip = \strtolower($ip);

        // convert hostname to IP
        if (!\preg_match('/^[0-9a-f.:]++$/u', $ip)) {
            $ip = \gethostbyname($ip);
        }

        $resultsCz88 = self::findFromCz88($ip);
        $resultsIpip = self::findFromIpip($ip);

        // use ipip's as the primary results
        $results = $resultsIpip;

        if (\count($results) < 3 || self::isInvalidEntry($results, 0)) {
            return [];
        }

        // use the ISP entry from cz88 DB
        if (
            !self::isInvalidEntry($resultsCz88, 0) &&
            !self::isInvalidEntry($resultsCz88, self::CZ88_ISP)
        ) {
            $results[self::IDX_ISP] = $resultsCz88[self::CZ88_ISP];
        }

        return $results;
    }

    /**
     * Look up IP location information from cz88 DB.
     *
     * @param string $ip the IP string
     *
     * @throws \Exception invalid db file
     *
     * @return array the IP location results
     */
    private static function findFromCz88(string $ip): array
    {
        return \explode("\t", self::findFromCz88String($ip));
    }

    /**
     * Look up IP location information from ipip DB.
     *
     * @see https://github.com/ipipdotnet/ipdb-php
     *
     * @param string $ip the IP string
     *
     * @return array the IP location results
     */
    private static function findFromIpip(string $ip): array
    {
        self::$ipipCity = self::$ipipCity ?? new ipipCity(self::$options['ipipDb']);

        return self::$ipipCity->find($ip, 'CN');
    }

    /**
     * Look up IP location information from cz88 DB.
     *
     * @param string $ip the IP string
     *
     * @throws \Exception invalid db file
     *
     * @return string the IP location results (delimited with \t)
     */
    private static function findFromCz88String(string $ip): string
    {
        if (!$fd = \fopen(self::$options['cz88Db'], 'r')) {
            throw new \Exception('Invalid qqwry.dat file!');
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

        if (
            !self::$options['cz88DbIsUtf8'] &&
            // iconv may fail and return false
            ($ipaddrU8 = @\iconv('gb2312', 'utf-8', $ipaddr))
        ) {
            $ipaddr = $ipaddrU8;
        }

        return $ipaddr;
    }

    /**
     * Determine if invalid entry.
     *
     * @param array $array the array
     * @param int   $index the index
     *
     * @return bool true if invalid entry, False otherwise
     */
    private static function isInvalidEntry(array $array, int $index): bool
    {
        $data = $array[$index] ?? null;

        return !isset($data) || $data === '' || $data === '-' || \strtoupper($data) === 'N/A';
    }
}
