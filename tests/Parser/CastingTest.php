<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Parser;

use Exception;
use Flat3\Lodata\Drivers\SQL\SQLExpression;
use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CastingTest extends Expression
{
    public function test_01()
    {
        $this->assertCast([2, 'sqlsrv' => '2'], "cast('2', 'Edm.Int32')");
    }

    public function test_02()
    {
        $this->assertCast([1, 'sqlsrv' => '1'], "cast('1', 'Edm.Int32')");
    }

    public function test_03()
    {
        $this->assertCast('1', "cast(1, 'Edm.String')");
    }

    public function test_04()
    {
        $this->assertCast([1, 'pgsql' => true, 'sqlsrv' => '1'], "cast(1, 'Edm.Boolean')");
    }

    public function test_05()
    {
        $this->assertCast([0, 'pgsql' => false, 'sqlsrv' => '0'], "cast(0, 'Edm.Boolean')");
    }

    public function test_06()
    {
        $this->assertCast('2000-01-01', "cast('2000-01-01', 'Edm.Date')");
    }

    public function test_07()
    {
        $this->assertCast(
            [
                '2001-01-01 09:01:02.000',
                'sqlite' => '2001-01-01T09:01:02+00:00',
                'pgsql' => '2001-01-01 09:01:02',
                'sqlsrv' => '2001-01-01 09:01:02.0000000 +00:00',
            ],
            "cast('2001-01-01T09:01:02+00:00', 'Edm.DateTimeOffset')"
        );
    }

    public function test_08()
    {
        $this->assertCast([2.0, 'pgsql' => '2', 'sqlsrv' => '2.0'], "cast(2, 'Edm.Double')");
    }

    public function test_09()
    {
        $this->assertCast(NotImplementedException::class, "cast('P1D', 'Edm.Duration')");
    }

    public function test_10()
    {
        $this->assertCast(NotImplementedException::class, "cast('0cfa779c-c41d-11ed-967e-b3bff6c61c95', 'Edm.Guid')");
    }

    public function test_11()
    {
        $this->assertCast([23, 'sqlsrv' => '23'], "cast('23', 'Edm.Int16')");
    }

    public function test_12()
    {
        $this->assertCast([233333, 'sqlsrv' => '233333'], "cast('233333', 'Edm.Int32')");
    }

    public function test_13()
    {
        $this->assertCast([23333333333333, 'sqlsrv' => '23333333333333'], "cast('23333333333333', 'Edm.Int64')");
    }

    public function test_14()
    {
        $this->assertCast([2, 'sqlsrv' => '2'], "cast('2', 'Edm.SByte')");
    }

    public function test_15()
    {
        $this->assertCast([2.0, 'pgsql' => '2', 'sqlsrv' => '2.0'], "cast(2, 'Edm.Single')");
    }

    public function test_16()
    {
        $this->assertCast([2.2, 'pgsql' => '2.2', 'sqlsrv' => '2.2000000000000002'], "cast('2.2', 'Edm.Single')");
    }

    public function test_17()
    {
        $this->assertCast([
            '23:23:23',
            'mysql' => '23:23:23.000',
            'sqlsrv' => '23:23:23.000'
        ], "cast('23:23:23', 'Edm.TimeOfDay')");
    }

    public function test_18()
    {
        $this->assertCast('2001', "cast(year(2001-01-01T09:01:02.400Z), 'Edm.String')");
    }

    public function test_19()
    {
        $this->assertCast([-2, 'mysql' => '18446744073709551614', 'sqlsrv' => '-2'], "cast('-2', 'UInt32')");
    }

    public function test_20()
    {
        $this->assertCast([-99, 'mysql' => '18446744073709551517', 'sqlsrv' => '-99'], "cast('-99', 'UInt64')");
    }

    public function test_21()
    {
        $this->assertCast([-2, 'mysql' => '18446744073709551614', 'sqlsrv' => '-2'], "cast('-2', 'UInt16')");
    }

    public function test_22()
    {
        $this->assertCast(null, "cast(null, 'Edm.String')");
    }

    public function test_23()
    {
        $this->assertCast(null, "cast(null, 'Edm.Boolean')");
    }

    public function test_24()
    {
        $this->assertCast([
            '2001-01-01 09:01:02.400',
            'sqlite' => '2001-01-01 09:01:02',
            'sqlsrv' => '2001-01-01 09:01:02'
        ], "cast(2001-01-01T09:01:02.400Z, 'Edm.String')");
    }

    public function test_25()
    {
        $this->assertCast([2001, 'sqlsrv' => '2001'], "year(cast('2001-01-01T14:14:00+00:00', 'Edm.DateTimeOffset'))");
    }

    public function test_26()
    {
        $this->assertCast('2000', "cast(2000, 'Edm.String')");
    }

    public function test_27()
    {
        $this->assertCast(
            '2001-01-01',
            "cast(2001-01-01, 'Edm.String')"
        );
    }

    public function test_28()
    {
        $this->assertCast(
            ['2001-01-01', 'sqlite' => '2001-01-01 09:01:02'],
            "cast(2001-01-01T09:01:02.400Z, 'Edm.Date')"
        );
    }

    public function test_29()
    {
        $this->assertCast(
            ['2001-01-01 09:01:02', 'mysql' => '09:01:02.400', 'pgsql' => '09:01:02.4', 'sqlsrv' => '09:01:02.000'],
            "cast(2001-01-01T09:01:02.400Z, 'Edm.TimeOfDay')"
        );
    }

    public function test_30()
    {
        $this->assertCast(null, "cast(null, 'Edm.String')");
    }

    public function test_31()
    {
        $this->assertCast(['hello', 'sqlsrv' => QueryException::class], "cast('hello', 'Edm.Binary')");
    }

    protected function assertCast($expected, string $expression)
    {
        $set = new SQLEntitySet('test', new EntityType('test'));
        $parser = $set->getFilterParser();

        if (is_array($expected)) {
            $expected = array_key_exists($set->getDriver(), $expected) ? $expected[$set->getDriver()] : $expected[0];
        }

        if (is_a($expected, Exception::class, true)) {
            $this->expectException($expected);
        }

        $container = new SQLExpression($set);
        $parser->pushEntitySet($set);
        $tree = $parser->generateTree($expression);
        $container->evaluate($tree);

        $result = Arr::first(DB::selectOne('SELECT '.$container->getStatement(), $container->getParameters()));

        if (is_resource($result)) {
            $result = stream_get_contents($result);
        }

        if (version_compare(PHP_VERSION, '8.1', '<')) {
            switch (true) {
                case is_float($expected):
                    $expected = number_format($expected, 1, '.', '');
                    break;

                case is_null($expected):
                    break;

                default:
                    $expected = (string) $expected;
                    break;
            }
        }

        $this->assertSame($expected, $result);
    }
}
