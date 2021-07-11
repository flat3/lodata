<?php

namespace Flat3\Lodata\Tests\Unit\Parser;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Entity;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Expression\Evaluate;
use Flat3\Lodata\Expression\Parser\Filter;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\Date;
use Flat3\Lodata\Type\DateTimeOffset;
use Flat3\Lodata\Type\TimeOfDay;
use RuntimeException;

class EvaluateTest extends TestCase
{
    public function test_0()
    {
        $this->assertSameExpression(1, '1');
    }

    public function test_1()
    {
        $this->assertSameExpression(1.1, '1.1');
    }

    public function test_2()
    {
        $this->assertSameExpression('a', "'a'");
    }

    public function test_3()
    {
        $this->assertSameExpression(null, 'null');
    }

    public function test_4()
    {
        $this->assertNan($this->evaluate('NaN'));
    }

    public function test_5()
    {
        $this->assertInfinite($this->evaluate('INF'));
    }

    public function test_6()
    {
        $this->assertInfinite(-$this->evaluate('-INF'));
    }

    public function test_7()
    {
        $this->assertSameExpression('2001-01-01', '2001-01-01');
    }

    public function test_8()
    {
        $this->assertSameExpression('14:14:14.000000', '14:14:14');
    }

    public function test_9()
    {
        $this->assertSameExpression('14:14:14.000001', '14:14:14.000001');
    }

    public function test_10()
    {
        $this->assertSameExpression('2020-01-01T23:23:23+00:00', '2020-01-01T23:23:23+00:00');
    }

    public function test_11()
    {
        $this->assertSameExpression(true, 'true');
    }

    public function test_12()
    {
        $this->assertSameExpression(false, 'false');
    }

    public function test_13()
    {
        $this->assertSameExpression(367485.122, 'P4DT6H4M45.121999999974S');
    }

    public function test_14()
    {
        $this->assertGuid(
            '2D1B80E8-0DAD-4EE7-AB6F-AE9FEC896290',
            $this->evaluate('2D1B80E8-0DAD-4EE7-AB6F-AE9FEC896290')
        );
    }

    public function test_20()
    {
        $this->assertTrueExpression('1 eq 1');
    }

    public function test_21()
    {
        $this->assertTrueExpression('1 eq 1.0');
    }

    public function test_22()
    {
        $this->assertTrueExpression('1.0 eq 1.0');
    }

    public function test_23()
    {
        $this->assertFalseExpression('1 eq 1.2');
    }

    public function test_24()
    {
        $this->assertTrueExpression('null eq null');
    }

    public function test_25()
    {
        $this->assertFalseExpression('null eq 4');
    }

    public function test_26()
    {
        $this->assertTrueExpression('-INF eq -INF');
    }

    public function test_27()
    {
        $this->assertTrueExpression('INF eq INF');
    }

    public function test_28()
    {
        $this->assertFalseExpression('NaN eq NaN');
    }

    public function test_28a()
    {
        $this->assertTrueExpression('2001-01-01 eq 2001-01-01');
    }

    public function test_28b()
    {
        $this->assertTrueExpression('14:14:00 eq 14:14:00');
    }

    public function test_28c()
    {
        $this->assertTrueExpression('P1D eq P1D');
    }

    public function test_28d()
    {
        $this->assertTrueExpression('2001-01-01T14:14:00+00:00 eq 2001-01-01T14:14:00+00:00');
    }

    public function test_29()
    {
        $this->assertTrueExpression('4 gt 3');
    }

    public function test_30()
    {
        $this->assertTrueExpression('true gt false');
    }

    public function test_31()
    {
        $this->assertTrueExpression('INF gt 28347395734');
    }

    public function test_32()
    {
        $this->assertTrueExpression('-12387238 gt -INF');
    }

    public function test_33()
    {
        $this->assertFalseExpression('4 gt null');
    }

    public function test_34()
    {
        $this->assertFalseExpression('null gt 4');
    }

    public function test_34a()
    {
        $this->assertTrueExpression('2001-01-02 gt 2001-01-01');
    }

    public function test_34b()
    {
        $this->assertTrueExpression('14:14:01 gt 14:14:00');
    }

    public function test_34c()
    {
        $this->assertTrueExpression('P4DT6H4M45S gt P1D');
    }

    public function test_34d()
    {
        $this->assertTrueExpression('2001-01-02T14:14:00+00:00 gt 2001-01-01T14:14:00+00:00');
    }

    public function test_35()
    {
        $this->assertTrueExpression('4 ge 3');
    }

    public function test_36()
    {
        $this->assertTrueExpression('true ge false');
    }

    public function test_37()
    {
        $this->assertTrueExpression('INF ge 28347395734');
    }

    public function test_38()
    {
        $this->assertTrueExpression('-12387238 ge -INF');
    }

    public function test_39()
    {
        $this->assertFalseExpression('4 ge null');
    }

    public function test_40()
    {
        $this->assertFalseExpression('null ge 4');
    }

    public function test_41()
    {
        $this->assertTrueExpression('3 lt 4');
    }

    public function test_42()
    {
        $this->assertTrueExpression('false lt true');
    }

    public function test_43()
    {
        $this->assertTrueExpression('28347395734 lt INF');
    }

    public function test_44()
    {
        $this->assertTrueExpression('-INF lt -12387238');
    }

    public function test_45()
    {
        $this->assertFalseExpression('null lt 4');
    }

    public function test_46()
    {
        $this->assertFalseExpression('4 lt null');
    }

    public function test_46a()
    {
        $this->assertTrueExpression('2001-01-01 lt 2001-01-02');
    }

    public function test_46b()
    {
        $this->assertTrueExpression('14:14:00 lt 14:14:01');
    }

    public function test_46c()
    {
        $this->assertTrueExpression('P4DT6H4M45S lt P5D');
    }

    public function test_46d()
    {
        $this->assertTrueExpression('2001-01-01T14:14:00+00:00 lt 2001-01-01T14:15:00+00:00');
    }

    public function test_47()
    {
        $this->assertTrueExpression('3 le 4');
    }

    public function test_48()
    {
        $this->assertTrueExpression('false le true');
    }

    public function test_49()
    {
        $this->assertTrueExpression('28347395734 le INF');
    }

    public function test_50()
    {
        $this->assertTrueExpression('-INF le -12387238');
    }

    public function test_51()
    {
        $this->assertFalseExpression('null le 4');
    }

    public function test_52()
    {
        $this->assertFalseExpression('4 le null');
    }

    public function test_53()
    {
        $this->assertTrueExpression('true and true');
    }

    public function test_54()
    {
        $this->assertFalseExpression('true and false');
    }

    public function test_55()
    {
        $this->assertFalseExpression('false and null');
    }

    public function test_56()
    {
        $this->assertNullExpression('true and null');
    }

    public function test_57()
    {
        $this->assertTrueExpression('true or true');
    }

    public function test_58()
    {
        $this->assertTrueExpression('true or false');
    }

    public function test_59()
    {
        $this->assertTrueExpression('true or null');
    }

    public function test_60()
    {
        $this->assertNullExpression('false or null');
    }

    public function test_61()
    {
        $this->assertTrueExpression('not false');
    }

    public function test_62()
    {
        $this->assertFalseExpression('not true');
    }

    public function test_63()
    {
        $this->assertNullExpression('not null');
    }

    public function test_64()
    {
        $this->assertTrueExpression('1 in (1,2)');
    }

    public function test_65()
    {
        $this->assertFalseExpression('1 in (3,2)');
    }

    public function test_66()
    {
        $this->assertTrueExpression("1 in ('1',2)");
    }

    public function test_70()
    {
        $this->assertNullExpression('1 add null');
    }

    public function test_71()
    {
        $this->assertSameExpression(3, '1 add 2');
    }

    public function test_72()
    {
        $this->assertSameExpression(3.1, '1 add 2.1');
    }

    public function test_73()
    {
        $this->assertSameExpression('2020-01-01T23:24:27+00:00', '2020-01-01T23:23:23+00:00 add PT1M4S');
    }

    public function test_74()
    {
        $this->assertSameExpression(7.0, 'PT3.5S add PT3.5S');
    }

    public function test_75()
    {
        $this->assertSameExpression('2001-01-02', '2001-01-01 add P1D');
    }

    public function test_76()
    {
        $this->assertSameExpression('2001-01-02', 'P1D add 2001-01-01');
    }

    public function test_77()
    {
        $this->assertBadExpression("4 add 'a'");
    }

    public function test_78()
    {
        $this->assertNullExpression('1 sub null');
    }

    public function test_79()
    {
        $this->assertSameExpression(-1, '1 sub 2');
    }

    public function test_80()
    {
        $this->assertSameExpression(-1.1, '1 sub 2.1');
    }

    public function test_81()
    {
        $this->assertSameExpression('2020-01-01T23:22:19+00:00', '2020-01-01T23:23:23+00:00 sub PT1M4S');
    }

    public function test_82()
    {
        $this->assertSameExpression(0.0, 'PT3.5S sub PT3.5S');
    }

    public function test_83()
    {
        $this->assertSameExpression('2000-12-31', '2001-01-01 sub P1D');
    }

    public function test_84()
    {
        $this->assertSameExpression('2000-12-31', 'P1D sub 2001-01-01');
    }

    public function test_85()
    {
        $this->assertBadExpression("4 sub 'a'");
    }

    public function test_86()
    {
        $this->assertSameExpression(-4, '-4');
    }

    public function test_87()
    {
        $this->assertSameExpression(-4.1, '-4.1');
    }

    public function test_88()
    {
        $this->assertSameExpression(-64.0, '-PT1M4S');
    }

    public function test_90()
    {
        $this->assertSameExpression(12, '4 mul 3');
    }

    public function test_91()
    {
        $this->assertSameExpression(-12.4, '-4 mul 3.1');
    }

    public function test_92()
    {
        $this->assertSameExpression(259200.0, 'P1D mul 3');
    }

    public function test_93()
    {
        $this->assertSameExpression(-259200.0, 'P1D mul -3');
    }

    public function test_94()
    {
        $this->assertSameExpression(-302400.0, 'P1D mul -3.5');
    }

    public function test_100()
    {
        $this->assertSameExpression(1, '4 div 3');
    }

    public function test_101()
    {
        $this->assertSameExpression(-1.2903225806451613, '-4 div 3.1');
    }

    public function test_102()
    {
        $this->assertSameExpression(28800.0, 'P1D div 3');
    }

    public function test_103()
    {
        $this->assertSameExpression(-28800.0, 'P1D div -3');
    }

    public function test_104()
    {
        $this->assertSameExpression(-24685.714285714286, 'P1D div -3.5');
    }

    public function test_105()
    {
        $this->assertBadExpression('P1D div PT4H');
    }

    public function test_106()
    {
        $this->assertBadExpression('4 div 0');
    }

    public function test_107()
    {
        $this->assertSameExpression(INF, '4.1 div 0');
    }

    public function test_108()
    {
        $this->assertSameExpression(-INF, '-4.1 div 0');
    }

    public function test_109()
    {
        $this->assertNan($this->evaluate('0.0 div 0'));
    }

    public function test_110()
    {
        $this->assertBadExpression('0 div 0');
    }

    public function test_111()
    {
        $this->assertSameExpression(1.3333333333333333, '4 divby 3');
    }

    public function test_112()
    {
        $this->assertSameExpression(-1.2903225806451613, '-4 divby 3.1');
    }

    public function test_113()
    {
        $this->assertSameExpression(28800.0, 'P1D divby 3');
    }

    public function test_114()
    {
        $this->assertSameExpression(-28800.0, 'P1D divby -3');
    }

    public function test_115()
    {
        $this->assertSameExpression(-24685.714285714286, 'P1D divby -3.5');
    }

    public function test_116()
    {
        $this->assertBadExpression('P1D divby PT4H');
    }

    public function test_117()
    {
        $this->assertBadExpression('4 divby 0');
    }

    public function test_118()
    {
        $this->assertSameExpression(INF, '4.1 divby 0');
    }

    public function test_119()
    {
        $this->assertSameExpression(-INF, '-4.1 divby 0');
    }

    public function test_120()
    {
        $this->assertNan($this->evaluate('0.0 divby 0'));
    }

    public function test_121()
    {
        $this->assertBadExpression('0 divby 0');
    }

    public function test_122()
    {
        $this->assertSameExpression(1.0, '4 mod 3');
    }

    public function test_123()
    {
        $this->assertSameExpression(-1.0, '-4 mod 3');
    }

    public function test_124()
    {
        $this->assertBadExpression('4 mod 0');
    }

    public function assertTrueExpression($expression): void
    {
        $this->assertTrue($this->evaluate($expression));
    }

    public function assertFalseExpression($expression): void
    {
        $this->assertFalse($this->evaluate($expression));
    }

    public function assertNullExpression($expression): void
    {
        $this->assertNull($this->evaluate($expression));
    }

    public function assertSameExpression($expected, $expression): void
    {
        $this->assertSame($expected, $this->evaluate($expression));
    }

    public function assertBadExpression($expression): void
    {
        try {
            $this->evaluate($expression);
            throw new RuntimeException('Failed to throw exception');
        } catch (BadRequestException $e) {
            return;
        }
    }

    public function evaluate(string $expression, ?Entity $item = null)
    {
        $transaction = new Transaction();
        $parser = new Filter($transaction);
        $tree = $parser->generateTree($expression);

        $result = Evaluate::eval($tree, $item);

        switch (true) {
            case $result instanceof TimeOfDay:
                return $result->get()->toTimeString('microseconds');

            case $result instanceof Date:
                return $result->get()->toDateString();

            case $result instanceof DateTimeOffset:
                return $result->get()->format('c');

            case $result instanceof Primitive:
                return $result->get();
        }

        return $result;
    }
}