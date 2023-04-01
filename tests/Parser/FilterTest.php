<?php

namespace Flat3\Lodata\Tests\Parser;

class FilterTest extends Expression
{
    public function test_0()
    {
        $this->assertFilter('origin eq "test"');
    }

    public function test_1()
    {
        $this->assertFilter("origin eq 'test'");
    }

    public function test_2()
    {
        $this->assertFilter("origin eq 'test");
    }

    public function test_3()
    {
        $this->assertFilter('id eq 4');
    }

    public function test_4()
    {
        $this->assertFilter('id gt 4');
    }

    public function test_5()
    {
        $this->assertFilter('id lt 4');
    }

    public function test_6()
    {
        $this->assertFilter('id ge 4');
    }

    public function test_7()
    {
        $this->assertFilter('id le 4');
    }

    public function test_8()
    {
        $this->assertFilter('id eq test');
    }

    public function test_9()
    {
        $this->assertFilter("origin in ('a', 'b', 'c')");
    }

    public function test_a()
    {
        $this->assertFilter("origin in ('a')");
    }

    public function test_b()
    {
        $this->assertFilter('id in (4, 3)');
    }

    public function test_c()
    {
        $this->assertFilter('id lt 4 and id gt 2');
    }

    public function test_d()
    {
        $this->assertFilter('id lt 4 or id gt 2');
    }

    public function test_e()
    {
        $this->assertFilter('id lt 4 or id lt 3 or id lt 2');
    }

    public function test_f()
    {
        $this->assertFilter('id lt 4 or id lt 3 and id lt 2');
    }

    public function test_10()
    {
        $this->assertFilter('id lt 4 or id in (3, 1) and id ge 2');
    }

    public function test_11()
    {
        $this->assertFilter('(id lt 4 and (id ge 7 or id gt 3)');
    }

    public function test_12()
    {
        $this->assertFilter('(id lt 4 a');
    }

    public function test_13()
    {
        $this->assertFilter('(id lt 4 and id ge 7) or id gt 3');
    }

    public function test_14()
    {
        $this->assertFilter('id lt 4 or (id gt 3 and id gt 2)');
    }

    public function test_15()
    {
        $this->assertFilter('(id lt 4 and id ge 7) or (id gt 3 and id gt 2)');
    }

    public function test_16()
    {
        $this->assertFilter('id add 3.14 eq 1.59');
    }

    public function test_17()
    {
        $this->assertFilter('id in (1.59, 2.14)');
    }

    public function test_18()
    {
        $this->assertFilter('(id add 3.14) in (1.59, 2.14) or (id gt -2.40 and id gt 4 add 5)');
    }

    public function test_19()
    {
        $this->assertFilter('id add 3.14 add 5 in (1.59, 2.14)');
    }

    public function test_1a()
    {
        $this->assertFilter('id add 3.14 in (1.59, 2.14)');
    }

    public function test_1b()
    {
        $this->assertFilter('id add 3.14 in (1.59, 2.14) or (id gt -2.40 and id gt 4 add 5)');
    }

    public function test_1c()
    {
        $this->assertFilter("not (contains(origin,'a')) and ((origin eq 'abcd') or (origin eq 'e'))");
    }

    public function test_1d()
    {
        $this->assertFilter("not (origin eq 'a')");
    }

    public function test_1e()
    {
        $this->assertFilter("origin eq 'b' and not (origin eq 'a')");
    }

    public function test_1f()
    {
        $this->assertFilter("origin eq 'b' or not (origin eq 'a')");
    }

    public function test_1g()
    {
        $this->assertFilter('-2.40');
    }

    public function test_20()
    {
        $this->assertFilter("contains(origin, 'b')");
    }

    public function test_21()
    {
        $this->assertFilter("endswith(origin, 'b')");
    }

    public function test_22()
    {
        $this->assertFilter("concat(origin, 'abc') eq '123abc'");
    }

    public function test_23()
    {
        $this->assertFilter("concat(origin, 'abc', 4.0) eq '123abc'");
    }

    public function test_24()
    {
        $this->assertFilter("concat(origin, id) eq '123abc'");
    }

    public function test_25()
    {
        $this->assertFilter("concat(origin, concat(id, 4)) eq '123abc'");
    }

    public function test_26()
    {
        $this->assertFilter("indexof(origin,'abc123') eq 1");
    }

    public function test_27()
    {
        $this->assertFilter('length(origin) eq 1');
    }

    public function test_28()
    {
        $this->assertFilter("substring(origin,1) eq 'abc123'");
    }

    public function test_29()
    {
        $this->assertFilter("substring(origin,1,4) eq 'abc123'");
    }

    public function test_2a()
    {
        $this->assertFilter("matchesPattern(origin,'^A.*e$')");
    }

    public function test_2b()
    {
        $this->assertFilter("tolower(origin) eq 'abc123'");
    }

    public function test_2c()
    {
        $this->assertFilter("toupper(origin) eq 'abc123'");
    }

    public function test_2d()
    {
        $this->assertFilter("trim(origin) eq 'abc123'");
    }

    public function test_2e()
    {
        $this->assertFilter('ceiling(origin) eq 4');
    }

    public function test_2f()
    {
        $this->assertFilter('floor(origin) eq 4');
    }

    public function test_30()
    {
        $this->assertFilter('origin eq 4 div 3');
    }

    public function test_31()
    {
        $this->assertFilter('origin eq 4 divby 3');
    }

    public function test_32()
    {
        $this->assertFilter('origin eq 4 add 3');
    }

    public function test_33()
    {
        $this->assertFilter('origin eq 4 sub 3');
    }

    public function test_34()
    {
        $this->assertFilter('origin eq 4 mul 3');
    }

    public function test_35()
    {
        $this->assertFilter('origin eq 4 mod 3');
    }

    public function test_36()
    {
        $this->assertFilter('origin eq 4');
    }

    public function test_37()
    {
        $this->assertFilter('origin gt 4');
    }

    public function test_38()
    {
        $this->assertFilter('origin ge 4');
    }

    public function test_39()
    {
        $this->assertFilter('origin in (4,3)');
    }

    public function test_3a()
    {
        $this->assertFilter('origin lt 4');
    }

    public function test_3b()
    {
        $this->assertFilter('origin le 4');
    }

    public function test_3c()
    {
        $this->assertFilter('origin ne 4');
    }

    public function test_3d()
    {
        $this->assertFilter('origin eq true');
    }

    public function test_3e()
    {
        $this->assertFilter('origin eq false');
    }

    public function test_3f()
    {
        $this->assertFilter('origin eq 2000-01-01');
    }

    public function test_40()
    {
        $this->assertFilter('origin eq 2000-01-01T12:34:59Z');
    }

    public function test_41()
    {
        $this->assertFilter('origin eq 04:11:12');
    }

    public function test_42()
    {
        $this->assertFilter('origin eq 4AA33245-E2D1-470D-9433-01AAFCF0C83F');
    }

    public function test_43()
    {
        $this->assertFilter('origin eq PT1M');
    }

    public function test_44()
    {
        $this->assertFilter('origin eq PT36H');
    }

    public function test_45()
    {
        $this->assertFilter('origin eq P10DT2H30M');
    }

    public function test_46()
    {
        $this->assertFilter('round(origin) eq 4',);
    }

    public function test_47()
    {
        $this->assertFilter('date(origin) eq 2001-01-01');
    }

    public function test_48()
    {
        $this->assertFilter('day(origin) eq 4');
    }

    public function test_49()
    {
        $this->assertFilter('hour(origin) eq 3');
    }

    public function test_4a()
    {
        $this->assertFilter('minute(origin) eq 33');
    }

    public function test_4b()
    {
        $this->assertFilter('month(origin) eq 11');
    }

    public function test_4c()
    {
        $this->assertFilter('now() eq 10:00:00');
    }

    public function test_4d()
    {
        $this->assertFilter('second(origin) eq 44');
    }

    public function test_4e()
    {
        $this->assertFilter('time(origin) eq 10:00:00');
    }

    public function test_4f()
    {
        $this->assertFilter('year(origin) eq 1999');
    }

    public function test_50()
    {
        $this->assertFilter("endswith(origin,'a')");
    }

    public function test_51()
    {
        $this->assertFilter("indexof(origin,'a') eq 1");
    }

    public function test_52()
    {
        $this->assertFilter("startswith(origin,'a')");
    }

    public function test_53()
    {
        $this->assertFilter('origin eq 2000-01-01T12:34:59+01:00');
    }

    public function test_54()
    {
        $this->assertFilter('origin eq 2000-01-01T12:34:59-01:00');
    }

    public function test_55()
    {
        $this->assertFilter("startswith(origin,'Veniam et') eq true");
    }

    public function test_56()
    {
        $this->assertFilter("true eq startswith(origin,'Veniam et')");
    }

    public function test_57()
    {
        $this->assertFilter("startswith(origin,'Veniam et') gt 4");
    }

    public function test_58()
    {
        $this->assertFilter("4 lt startswith(origin,'Veniam et')");
    }

    public function test_59()
    {
        $this->assertFilter("endswith(origin,'Veniam et') eq true and startswith(origin,'Veniam et') eq true");
    }

    public function test_5a()
    {
        $this->assertFilter("endswith(origin,'Veniam et') eq true and not (startswith(origin,'Veniam et') eq true)");
    }

    public function test_5c()
    {
        $this->assertFilter("endswith(origin,'Veniam et') eq true and not (startswith(origin,'Veniam et') eq true)");
    }

    public function test_5f()
    {
        $this->assertFilter('origin gt (now() sub PT3M)');
    }

    public function test_60()
    {
        $this->assertFilter("origin EQ 'lax'");
    }

    public function test_70()
    {
        $this->assertFilter("priority has Priorities'high'");
    }

    public function test_71()
    {
        $this->assertFilter("priority has Priorities'high,medium'");
    }

    public function test_72()
    {
        $this->assertFilter("priority has com.example.odata.Priorities'high,medium'");
    }

    public function test_73()
    {
        $this->assertFilter("(contains(tolower(cast(origin, 'Edm.String')),'alpha')) or (contains(tolower(cast(origin, 'Edm.String')),'alpha'))");
    }

    public function test_74()
    {
        $this->assertFilter("origin eq null");
    }
}
