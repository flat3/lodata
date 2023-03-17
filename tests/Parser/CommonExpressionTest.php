<?php

namespace Flat3\Lodata\Tests\Parser;

use Carbon\Carbon;
use Flat3\Lodata\Expression\Parser\Common;
use Flat3\Lodata\Expression\Parser\Filter;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Type\Date;
use Flat3\Lodata\Type\DateTimeOffset;
use Flat3\Lodata\Type\TimeOfDay;
use RuntimeException;

class CommonExpressionTest extends Expression
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
        $this->assertSame(
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

    public function test_130()
    {
        $this->assertSameExpression('hello world', "concat(concat('hello', ' '), 'world')");
    }

    public function test_131()
    {
        $this->assertBadExpression('concat(1,2)');
    }

    public function test_132()
    {
        $this->assertSameExpression(true, "contains('hello', 'hell')");
    }

    public function test_133()
    {
        $this->assertSameExpression(false, "contains('hello', 'world')");
    }

    public function test_134()
    {
        $this->assertBadExpression("contains('hello', 4)");
    }

    public function test_135()
    {
        $this->assertSameExpression(true, "endswith('hello', 'ello')");
    }

    public function test_136()
    {
        $this->assertSameExpression(false, "endswith('hello', 'hel')");
    }

    public function test_137()
    {
        $this->assertBadExpression("endswith('hello', 3)");
    }

    public function test_138()
    {
        $this->assertSameExpression(2, "indexof('hello', 'll')");
    }

    public function test_139()
    {
        $this->assertSameExpression(-1, "indexof('hello', 'world')");
    }

    public function test_140()
    {
        $this->assertSameExpression(3, "indexof('helLo', 'L')");
    }

    public function test_141()
    {
        $this->assertSameExpression(11, "length('hello world')");
    }

    public function test_141a()
    {
        $this->assertNullExpression("length(null)");
    }

    public function test_142()
    {
        $this->assertSameExpression(true, "startswith('hello', 'hell')");
    }

    public function test_143()
    {
        $this->assertSameExpression(false, "startswith('hello', 'world')");
    }

    public function test_144()
    {
        $this->assertSameExpression('lo', "substring('hello', 3)");
    }

    public function test_145()
    {
        $this->assertSameExpression('l', "substring('hello', 3, 1)");
    }

    public function test_146()
    {
        $this->assertSameExpression('lo', "substring('hello', 3, 99)");
    }

    public function test_150()
    {
        $this->assertSameExpression(true, "matchesPattern('Aire', '^A.*e$')");
    }

    public function test_151()
    {
        $this->assertSameExpression(false, "matchesPattern('hello', '^A.*e$')");
    }

    public function test_152()
    {
        $this->assertSameExpression('hello world', "tolower('Hello World')");
    }

    public function test_153()
    {
        $this->assertSameExpression('HELLO WORLD', "toupper('Hello World')");
    }

    public function test_154()
    {
        $this->assertSameExpression('hello world', "trim(' hello world  ')");
    }

    public function test_155()
    {
        $this->assertBadExpression("trim(4)");
    }

    public function test_156()
    {
        $this->assertSameExpression('2001-01-01', 'date(2001-01-01T00:01:02+00:00)');
    }

    public function test_157()
    {
        $this->assertBadExpression("date('hello')");
    }

    public function test_158()
    {
        $this->assertSameExpression(1, 'day(2001-01-01T00:01:02+00:00)');
    }

    public function test_159()
    {
        $this->assertSameExpression(1, 'day(2001-01-01)');
    }

    public function test_160()
    {
        $this->assertBadExpression("day('hello')");
    }

    public function test_161()
    {
        $this->assertSameExpression(0.4, 'fractionalseconds(13:13:13.4)');
    }

    public function test_162()
    {
        $this->assertSameExpression(0.4, 'fractionalseconds(2001-01-01T00:01:02.400Z)');
    }

    public function test_163()
    {
        $this->assertBadExpression('fractionalseconds(8)');
    }

    public function test_164()
    {
        $this->assertSameExpression(9, 'hour(2001-01-01T09:01:02.400Z)');
    }

    public function test_165()
    {
        $this->assertSameExpression(9, 'hour(09:01:02.400)');
    }

    public function test_166()
    {
        $this->assertBadExpression('hour(9)');
    }

    public function test_167()
    {
        $this->assertSameExpression(1, 'minute(2001-01-01T09:01:02.400Z)');
    }

    public function test_168()
    {
        $this->assertSameExpression(1, 'minute(09:01:02.400)');
    }

    public function test_169()
    {
        $this->assertBadExpression('minute(9)');
    }

    public function test_170()
    {
        $this->assertSameExpression(1, 'month(2001-01-01T09:01:02.400Z)');
    }

    public function test_171()
    {
        $this->assertSameExpression(7, 'month(1980-07-02)');
    }

    public function test_172()
    {
        $this->assertBadExpression('month(9)');
    }

    public function test_173()
    {
        $this->assertSameExpression(2, 'second(2001-01-01T09:01:02.400Z)');
    }

    public function test_174()
    {
        $this->assertSameExpression(2, 'second(09:01:02.400)');
    }

    public function test_175()
    {
        $this->assertBadExpression('second(9)');
    }

    public function test_176()
    {
        $this->assertSameExpression('09:01:02.000000', 'time(09:01:02)');
    }

    public function test_177()
    {
        $this->assertSameExpression(2001, 'year(2001-01-01T09:01:02.400Z)');
    }

    public function test_178()
    {
        $this->assertSameExpression(1980, 'year(1980-07-02)');
    }

    public function test_179()
    {
        $this->assertBadExpression('year(9)');
    }

    public function test_180()
    {
        $this->assertSameExpression('9999-12-31T23:59:59+00:00', 'maxdatetime()');
    }

    public function test_181()
    {
        $this->assertSameExpression('0001-01-01T00:00:00+00:00', 'mindatetime()');
    }

    public function test_182()
    {
        Carbon::withTestNow('2021-07-13T19:22:45+00:00', function () {
            $this->assertSameExpression('2021-07-13T19:22:45+00:00', 'now()');
        });
    }

    public function test_183()
    {
        $this->assertSameExpression(242, 'totaloffsetminutes(2001-01-01T00:01:02+04:02)');
    }

    public function test_184()
    {
        $this->assertSameExpression(86402.2, 'totalseconds(P1DT2.2S)');
    }

    public function test_190()
    {
        $this->assertSameExpression(5.0, 'ceiling(4.4)');
    }

    public function test_191()
    {
        $this->assertSameExpression(4.0, 'floor(4.8)');
    }

    public function test_192()
    {
        $this->assertSameExpression(5.0, 'round(4.5)');
    }

    public function test_200()
    {
        $this->assertSameExpression(1, "cast('1', 'Edm.Int32')");
    }

    public function test_201()
    {
        $this->assertSameExpression('1', "cast(1, 'Edm.String')");
    }

    public function test_202()
    {
        $this->assertTrueExpression("cast('true', 'Edm.Boolean')");
    }

    public function test_203()
    {
        $this->assertFalseExpression("cast('false', 'Edm.Boolean')");
    }

    public function test_204()
    {
        $this->assertSameExpression('2000-01-01', "cast('2000-01-01', 'Edm.Date')");
    }

    public function test_205()
    {
        $this->assertSameExpression(
            '2001-01-01T09:01:02+00:00',
            "cast('2001-01-01T09:01:02+00:00', 'Edm.DateTimeOffset')"
        );
    }

    public function test_206()
    {
        $this->assertSameExpression(2.0, "cast(2, 'Edm.Double')");
    }

    public function test_207()
    {
        $this->assertSameExpression(86400.0, "cast('P1D', 'Edm.Duration')");
    }

    public function test_208()
    {
        $this->assertSameExpression(
            '0CFA779C-C41D-11ED-967E-B3BFF6C61C95',
            "cast('0cfa779c-c41d-11ed-967e-b3bff6c61c95', 'Edm.Guid')"
        );
    }

    public function test_209()
    {
        $this->assertSameExpression(23, "cast('23', 'Edm.Int16')");
    }

    public function test_210()
    {
        $this->assertSameExpression(233333, "cast('233333', 'Edm.Int32')");
    }

    public function test_211()
    {
        $this->assertSameExpression(23333333333333, "cast('23333333333333', 'Edm.Int64')");
    }

    public function test_212()
    {
        $this->assertSameExpression(2, "cast('2', 'Edm.SByte')");
    }

    public function test_213()
    {
        $this->assertSameExpression(2.0, "cast(2, 'Edm.Single')");
    }

    public function test_214()
    {
        $this->assertSameExpression(2.2, "cast('2.2', 'Edm.Single')");
    }

    public function test_215()
    {
        $this->assertSameExpression('23:23:23.000000', "cast('23:23:23', 'Edm.TimeOfDay')");
    }

    public function test_220()
    {
        $this->assertSameExpression('2001', "cast(year(2001-01-01T09:01:02.400Z), 'Edm.String')");
    }

    public function test_221()
    {
        $this->assertSameExpression(4294967294, "cast('-2', 'UInt32')");
    }

    public function test_222()
    {
        $this->assertSameExpression(99, "cast('-99', 'UInt64')");
    }

    public function test_223()
    {
        $this->assertSameExpression(65534, "cast('-2', 'UInt16')");
    }

    public function test_231()
    {
        $this->assertNullExpression("cast(null, 'Edm.String')");
    }

    public function test_232()
    {
        $this->assertNullExpression("cast(null, 'Edm.Boolean')");
    }

    public function test_233()
    {
        $this->assertSameExpression('2001-01-01 09:01:02', "cast(2001-01-01T09:01:02.400Z, 'Edm.String')");
    }

    public function test_234()
    {
        $this->assertSameExpression(2001, "year(cast('2001-01-01T14:14:00+00:00', 'Edm.DateTimeOffset'))");
    }

    public function test_240()
    {
        $this->assertSameExpression('2000', "cast(2000, 'Edm.String')");
    }

    public function test_241()
    {
        $this->assertSameExpression('2001-01-01 00:00:00', "cast(2001-01-01, 'Edm.String')");
    }

    public function test_242()
    {
        $this->assertSameExpression('2001-01-01', "cast(2001-01-01T09:01:02.400Z, 'Edm.Date')");
    }

    public function test_243()
    {
        $this->assertSameExpression('09:01:02.400000', "cast(2001-01-01T09:01:02.400Z, 'Edm.TimeOfDay')");
    }

    public function evaluate(string $expression)
    {
        $parser = new Filter();
        $tree = $parser->generateTree($expression);

        $result = Common::evaluate($tree);

        switch (true) {
            case $result instanceof TimeOfDay:
                return $result->get()->toTimeString('microseconds');

            case $result instanceof Date:
                return $result->get()->toDateString();

            case $result instanceof DateTimeOffset:
                return $result->get()->format('c');

            case $result instanceof Primitive:
                return $result->get();

            case $result === null:
                return null;
        }

        throw new RuntimeException('Incorrect type returned');
    }
}
