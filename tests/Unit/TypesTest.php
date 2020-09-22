<?php

namespace Flat3\OData\Tests\Unit;

use Flat3\OData\Tests\TestCase;
use Flat3\OData\Type;

class TypesTest extends TestCase
{
    const inputs = [
        null, true, false, 'null', 'true', 'false', 0, 1, -1, INF, -INF, NAN, 0.1, -0.1, 'test', '', 'te\'st',
        -(2 ^ 8) - 1, -(2 ^ 16) + 1, -(2 ^ 32) + 1, -(2 ^ 64) + 1,
        (2 ^ 8) + 1, (2 ^ 16) + 1, (2 ^ 32) + 1, (2 ^ 64) + 1, PHP_INT_MAX, 'MTIz',
    ];

    public static $assertions = [
        Type\Binary::class => [
            'toUrl' => [
                [null, 'null'],
                ['aGVsbG8=', 'aGVsbG8'],
                ['7/s5mg==', '7_s5mg'],
                ['7_s5mg', '7_s5mg'],
                ['hello', 'hell'],
            ],
            'toJson' => [
                [null, null],
                ['aGVsbG8=', 'aGVsbG8='],
                ['7/s5mg==', '7/s5mg=='],
                ['7_s5mg', '7/s5mg=='],
                ['hello', 'hell'],
            ],
        ],
        Type\Boolean::class => [
            'toUrl' => [
                [null, 'null'],
                ['true', 'true'],
                ['false', 'false'],
                [true, 'true'],
                [false, 'false'],
                [0, 'false'],
                [1, 'true'],
                ['', 'false'],
                [NAN, 'true'],
            ],
            'toJson' => [
                [null, null],
                ['true', true],
                ['false', false],
                [true, true],
                [false, false],
                [0, false],
                [1, true],
                ['', false],
                [NAN, true],
            ],
        ],
        Type\Byte::class => [
            'toUrl' => [
                [null, 'null'],
                [0, '0'],
                [-1, '255'],
                [1, '1'],
                [255, '255'],
                [256, '0'],
                [INF, '0'],
                [NAN, '0'],
                [0.1, '0'],
                [1.2, '1'],
            ],
            'toJson' => [
                [null, null],
                [0, 0],
                [-1, 255],
                [1, 1],
                [255, 255],
                [256, 0],
                [INF, 0],
                [NAN, 0],
                [0.1, 0],
                [1.2, 1],
            ],
        ],
        Type\Date::class => [
            'toUrl' => [
                [null, 'null'],
                [0, '1970-01-01'],
                ['2020-01-01', '2020-01-01'],
                ['2020-01-02 23:23:23', '2020-01-02'],
            ],
            'toJson' => [
                [null, null],
                [0, '1970-01-01'],
                ['2020-01-01', '2020-01-01'],
                ['2020-01-02 23:23:23', '2020-01-02'],
            ],
        ],
        Type\DateTimeOffset::class => [
            'toUrl' => [
                [null, 'null'],
                [false, '1970-01-01T00%3A00%3A00%2B00%3A00'],
                [0, '1970-01-01T00%3A00%3A00%2B00%3A00'],
                ['2020-01-01', '2020-01-01T00%3A00%3A00%2B00%3A00'],
                ['2020-01-01 23:23:23', '2020-01-01T23%3A23%3A23%2B00%3A00'],
                ['2020-01-01T23:23:23+00:01', '2020-01-01T23%3A23%3A23%2B00%3A01'],
                ['2021-01-01T23%3A23%3A23%2B00%3A01', '2021-01-01T23%3A23%3A23%2B00%3A01'],
            ],
            'toJson' => [
                [null, null],
                [false, '1970-01-01T00:00:00+00:00'],
                [0, '1970-01-01T00:00:00+00:00'],
                ['2020-01-01', '2020-01-01T00:00:00+00:00'],
                ['2020-01-01 23:23:23', '2020-01-01T23:23:23+00:00'],
                ['2020-01-01T23:23:23+00:01', '2020-01-01T23:23:23+00:01'],
                ['2021-01-01T23%3A23%3A23%2B00%3A01', '2021-01-01T23:23:23+00:01'],
            ],
        ],
        Type\Decimal::class => [
            'toUrl' => [
                [null, 'null'],
                [0, '0'],
                [1.0, '1'],
                [1.1, '1.1'],
                [NAN, 'NaN'],
                [INF, 'INF'],
                [-INF, '-INF'],
                [-1, '-1'],
                [-1.0, '-1'],
                [2 ** 32, '4294967296'],
                [2 ** 64, '1.844674407371e+19'],
                [M_PI, '3.1415926535898'],
                [M_PI / 2 ** 16, '4.7936899621426e-5'],
            ],
            'toJson' => [
                [null, null],
                [0, 0.0],
                [1.0, 1.0],
                [1.1, 1.1],
                [NAN, 'NaN'],
                [INF, 'INF'],
                [-INF, '-INF'],
                [-1, -1.0],
                [-1.0, -1.0],
                [2 ** 32, 4294967296.0],
                [2 ** 64, 1.8446744073709552E+19],
                [M_PI, 3.141592653589793],
                [M_PI / 2 ** 16, 4.7936899621426287E-5],
            ],
            'toJsonIeee754' => [
                [null, null],
                [0, '0'],
                [1.0, '1.000000000000000'],
                [1.1, '1.100000000000000'],
                [NAN, 'NaN'],
                [INF, 'INF'],
                [-INF, '-INF'],
                [-1, '-1'],
                [-1.0, '-1'],
                [2 ** 32, '4294967296.000000'],
                [2 ** 64, '18446744073709551616'],
                [M_PI, '3.141592653589793'],
                [M_PI / 2 ** 16, '0.00004793689962142629'],
                [M_PI / 2 ** 32, '0.0000000007314590396335798'],
            ],
        ],
        Type\Duration::class => [
            'toUrl' => [
                [null, 'null'],
                [3, "'PT3S'"],
                [3.5, "'PT3.5S'"],
                [64, "'PT1M4S'"],
                [367485.122, "'P4DT6H4M45.121999999974S'"],
                [246834567345, "'P2856881DT13H35M45S'"],
                ['PT3S', "'PT3S'"],
                ['PT3.5S', "'PT3.5S'"],
                ['PT1M4S', "'PT1M4S'"],
                ['P4DT6H4M45.121999999974S', "'P4DT6H4M45.121999999974S'"],
                ['P2856881DT13H35M45S', "'P2856881DT13H35M45S'"],
            ],
            'toJson' => [
                [null, null],
                [3, 'PT3S'],
                [3.5, 'PT3.5S'],
                [64, 'PT1M4S'],
                [367485.122, 'P4DT6H4M45.121999999974S'],
                [246834567345, 'P2856881DT13H35M45S'],
                ['PT3S', 'PT3S'],
                ['PT3.5S', 'PT3.5S'],
                ['PT1M4S', 'PT1M4S'],
                ['P4DT6H4M45.121999999974S', 'P4DT6H4M45.121999999974S'],
                ['P2856881DT13H35M45S', 'P2856881DT13H35M45S'],
            ],
        ],
        Type\Guid::class => [
            'toUrl' => [
                [null, 'null'],
                ['2D1B80E8-0DAD-4EE7-AB6F-AE9FEC896290', '2D1B80E8-0DAD-4EE7-AB6F-AE9FEC896290'],
                ['00000000-0000-0000-0000-000000000000', '00000000-0000-0000-0000-000000000000'],
            ],
            'toJson' => [
                [null, null],
                ['2D1B80E8-0DAD-4EE7-AB6F-AE9FEC896290', '2D1B80E8-0DAD-4EE7-AB6F-AE9FEC896290'],
                ['00000000-0000-0000-0000-000000000000', '00000000-0000-0000-0000-000000000000'],
            ],
        ],
        Type\Int16::class => [
            'toUrl' => [
                [null, 'null'],
                [0, '0'],
                [1.1, '1'],
                [-1, '-1'],
                [512, '512'],
                [32768, '-32768'],
                [32767, '32767'],
                [-32767, '-32767'],
            ],
            'toJson' => [
                [null, null],
                [0, 0],
                [1.1, 1],
                [-1, -1],
                [512, 512],
                [32768, -32768],
                [32767, 32767],
                [-32767, -32767],
            ],
        ],
        Type\Int32::class => [
            'toUrl' => [
                [null, 'null'],
                [0, '0'],
                [1.1, '1'],
                [-1, '-1'],
                [512, '512'],
                [32768, '32768'],
                [32767, '32767'],
                [-32767, '-32767'],
                [2147483647, '2147483647'],
                [2147483648, '-2147483648'],
            ],
            'toJson' => [
                [null, null],
                [0, 0],
                [1.1, 1],
                [-1, -1],
                [512, 512],
                [32768, 32768],
                [32767, 32767],
                [-32767, -32767],
                [2147483647, 2147483647],
                [2147483648, -2147483648],
            ],
        ],
        Type\Int64::class => [
            'toUrl' => [
                [null, 'null'],
                [0, '0'],
                [1.1, '1'],
                [-1, '-1'],
                [512, '512'],
                [32768, '32768'],
                [32767, '32767'],
                [-32767, '-32767'],
                [2147483647, '2147483647'],
                [2147483648, '2147483648'],
                [9223372036854775807, '9223372036854775807'],
                [-9223372036854775807, '-9223372036854775807'],
            ],
            'toJson' => [
                [null, null],
                [0, 0],
                [1.1, 1],
                [-1, -1],
                [512, 512],
                [32768, 32768],
                [32767, 32767],
                [-32767, -32767],
                [2147483647, 2147483647],
                [2147483648, 2147483648],
                [9223372036854775807, 9223372036854775807],
                [-9223372036854775807, -9223372036854775807],
            ],
        ],
        Type\SByte::class => [
            'toUrl' => [
                [null, 'null'],
                [0, '0'],
                [-1, '-1'],
                [1, '1'],
                [128, '-128'],
                [255, '-1'],
                [256, '0'],
                [INF, '0'],
                [NAN, '0'],
                [0.1, '0'],
                [1.2, '1'],
            ],
            'toJson' => [
                [null, null],
                [0, 0],
                [-1, -1],
                [1, 1],
                [128, -128],
                [255, -1],
                [256, 0],
                [INF, 0],
                [NAN, 0],
                [0.1, 0],
                [1.2, 1],
            ],
        ],
        Type\Stream::class => [
            'toUrl' => [
                [null, 'null'],
                ['hello', "'hello'"],
                ['', "''"],
                ["hell'o", "'hell'o'"],
                [0, "'0'"],
            ],
            'toJson' => [
                [null, null],
                ['hello', 'hello'],
                ['', ''],
                ["hell'o", "hell'o"],
                [0, '0'],
            ],
        ],
        Type\String_::class => [
            'toUrl' => [
                [null, 'null'],
                ['hello', "'hello'"],
                ['', "''"],
                ["hell'o", "'hell''o'"],
                [0, "'0'"],
            ],
            'toJson' => [
                [null, null],
                ['hello', 'hello'],
                ['', ''],
                ["hell'o", "hell'o"],
                [0, '0'],
            ],
        ],
        Type\TimeOfDay::class => [
            'toUrl' => [
                [null, 'null'],
                [0, '00%3A00%3A00.000000'],
                ['2020-01-01', '00%3A00%3A00.000000'],
                ['2020-01-02 23:23:23', '23%3A23%3A23.000000'],
                ['2020-01-02 23:23:23.3333', '23%3A23%3A23.333300'],
            ],
            'toJson' => [
                [null, null],
                [0, '00:00:00.000000'],
                ['2020-01-01', '00:00:00.000000'],
                ['2020-01-02 23:23:23', '23:23:23.000000'],
                ['2020-01-02 23:23:23.3333', '23:23:23.333300'],
            ],
        ],
    ];

    public function test_types()
    {
        /** @var Type $type */
        foreach (self::$assertions as $type => $methods) {
            foreach ($methods as $method => $assertions) {
                foreach ($assertions as $assertion) {
                    list ($from, $to) = $assertion;
                    $this->assertSame($to, $type::type()->factory($from)->$method(),
                        $type.'('.(string) $from.')::'.$method.' -> '.$to);
                }
            }
        }
    }

    public $nulls = [
        Type\Binary::class => ['', ''],
        Type\Boolean::class => [false, "false"],
        Type\Byte::class => [0, '0'],
        Type\Date::class => ['1970-01-01', "1970-01-01"],
        Type\DateTimeOffset::class => ['1970-01-01T00:00:00+00:00', '1970-01-01T00%3A00%3A00%2B00%3A00'],
        Type\Decimal::class => [0.0, '0'],
        Type\Double::class => [0.0, '0'],
        Type\Duration::class => ['PT0S', "'PT0S'"],
        Type\Guid::class => ['00000000-0000-0000-0000-000000000000', '00000000-0000-0000-0000-000000000000'],
        Type\Int16::class => [0, '0'],
        Type\Int32::class => [0, '0'],
        Type\Int64::class => [0, '0'],
        Type\SByte::class => [0, '0'],
        Type\Single::class => [0.0, '0'],
        Type\Stream::class => ['', "''"],
        Type\String_::class => ['', "''"],
        Type\TimeOfDay::class => ['00:00:00.000000', '00%3A00%3A00.000000'],
    ];

    public function test_null()
    {
        /**
         * @var Type $clazz
         * @var array $values
         */
        foreach ($this->nulls as $clazz => $values) {
            $type = $clazz::type()->factory(null, true);
            $this->assertNull($type->toJson());

            $type = $clazz::type()->factory(null, false);
            $this->assertNotNull($type->toJson(), $clazz);
            $this->assertEquals($values[0], $type->toJson(), $clazz);
            $this->assertEquals($values[1], $type->toUrl(), $clazz);
        }
    }
}
