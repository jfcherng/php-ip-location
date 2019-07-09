<?php

declare(strict_types=1);

namespace Jfcherng\IpLocation\Test;

use Jfcherng\IpLocation\IpLocation;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 *
 * @internal
 */
final class IpLocationTest extends TestCase
{
    /**
     * PHPUnit routine for class.
     */
    public static function setupBeforeClass(): void
    {
        $ipFinder = IpLocation::getInstance();

        $ipFinder->setup([
            'ipipDb' => __DIR__ . '/../src/db/ipipfree.ipdb',
            'cz88Db' => __DIR__ . '/../src/db/qqwry.ipdb',
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
                    'country_name' => '中国',
                    'region_name' => '天津',
                    'city_name' => '天津',
                    'owner_domain' => '',
                    'isp_domain' => '教育网',
                ],
            ],
            [
                '0.0.0.0',
                [
                    'country_name' => '保留地址',
                    'region_name' => '保留地址',
                    'city_name' => '',
                    'owner_domain' => '',
                    'isp_domain' => '',
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
        $ipFinder = IpLocation::getInstance();

        $output = $ipFinder->find($input);

        static::assertSame($expected, $output);
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

        $ipFinder = IpLocation::getInstance();
        $output = $ipFinder->find('a.b.c.d');
    }
}
