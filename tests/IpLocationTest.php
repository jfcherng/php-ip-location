<?php

declare(strict_types=1);

namespace Jfcherng\IpLocation\Test;

use Jfcherng\IpLocation\IpLocation;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class IpLocationTest extends TestCase
{
    /**
     * PHPUnit routine for class.
     */
    public static function setupBeforeClass(): void
    {
        // 如果不想要使用內建的 IP 資料庫，請進行以下設定
        IpLocation::setup([
            // ipip 資料庫的路徑
            'ipipDb' => __DIR__ . '/../src/db/ipipfree.ipdb',
            // cz88 資料庫的路徑
            'cz88Db' => __DIR__ . '/../src/db/qqwry.dat',
            // cz88 資料庫是否為 UTF-8 編碼
            'cz88DbIsUtf8' => false,
        ]);
    }

    /**
     * Data provider for IpLocation::getGroupedOpcodes.
     *
     * @return array the data provider
     */
    public function getFindDataProvider(): array
    {
        return [
            [
                '202.113.245.255',
                [
                    '中国',
                    '天津',
                    '天津',
                    '天津工程师范学院教育网',
                ],
            ],
            [
                '0.0.0.0',
                [
                    '保留地址',
                    '保留地址',
                    '',
                    'IANA',
                ],
            ],
        ];
    }

    /**
     * Test the IpLocation::find.
     *
     * @covers       \Jfcherng\IpLocation\IpLocation::find
     * @dataProvider getFindDataProvider
     *
     * @param string $input    the input
     * @param array  $expected the expected
     */
    public function testFind(string $input, array $expected): void
    {
        $output = IpLocation::find($input);

        $this->assertSame($expected, $output);
    }

    /**
     * Test the IpLocation::find with flags.
     *
     * @covers \Jfcherng\IpLocation\IpLocation::find
     */
    public function testFindWithFlags(): void
    {
        $input = '202.113.245.255';
        $output = IpLocation::find($input, IpLocation::RET_ASSOCIATIVE);

        $expected = [
            'country' => '中国',
            'province' => '天津',
            'county' => '天津',
            'isp' => '天津工程师范学院教育网',
        ];

        $this->assertSame(
            \arraySortedRecursive($expected, 'asort'),
            \arraySortedRecursive($output, 'asort')
        );
    }

    /**
     * Test the IpLocation::find with an invalid IP.
     *
     * @covers \Jfcherng\IpLocation\IpLocation::find
     *
     * @param string $input    the input
     * @param array  $expected the expected
     */
    public function testFindWithInvalidIp(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $output = IpLocation::find('a.b.c.d');
    }
}
