<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Filter;

use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

abstract class Filter extends TestCase
{
    public function test_filter()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("name eq 'Alpha'")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_gt()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("age gt 3")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_ge()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("age ge 3")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_le()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("age le 3")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_lt()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("age lt 3")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_ne()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("age ne 3")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_in()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("age in (2,3)")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_has()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("colour has Colours'Blue'")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_has_multi()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("sock_colours has MultiColours'Blue,Green'")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_eq_null()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("age eq null")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_ne_null()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("age ne null")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_or()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("name eq 'Alpha' or name eq 'Gamma'")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_startswith()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("startswith(name, 'Alph')")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_substring_1()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("substring(name, 2) eq 'pha'")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_substring_2()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("substring(name, 2, 1) eq 'p'")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_not()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("not (name eq 'Alpha')")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_contains()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("contains(name, 't')")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_length()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("length(name) eq 4")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_indexof()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("indexof(name, 'pha') eq 2")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_round()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("round(age) eq 2")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_ceiling()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("ceiling(age) eq 3")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_floor()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("floor(age) eq 2")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_tolower()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("tolower(name) eq 'epsilon'")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_toupper()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("toupper(name) eq 'EPSILON'")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_trim()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("trim(' a') eq 'a'")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_matchespattern()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("matchesPattern(name, '^G')")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_concat()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("name eq concat('Ga','mma')")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_endswith()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("endswith(name, 'ta')")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_add()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("age add 10 eq 13")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_sub()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("age sub 10 eq -7")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_div()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("age div 2 eq 1.2")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_divby()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("age divby 10 eq 0.3")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_mul()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("age mul 10 eq 30")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_mod()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("age mod 3 eq 0")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_endswith_or()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("endswith(name, 'ta') or name eq 'Alpha'")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_day()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("day(dob) eq 4")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_date()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("date(dob) eq 2000-01-01")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_hour()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("hour(dob) eq 6")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_minute()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("minute(dob) eq 6")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_month()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("month(dob) eq 4")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_second()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("second(dob) eq 4")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_time()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("time(dob) eq 06:06:06")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_year()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("year(dob) eq 2000")
                ->path($this->entitySetPath)
        );
    }

    public function test_count_path_uses_filter()
    {
        $this->assertTextResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'/$count')
                ->text()
                ->filter('year(dob) eq 2000')
        );
    }

    public function test_count_uses_filter()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->count('true')
                ->filter('year(dob) eq 2000')
        );
    }

    public function test_path_filter()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath."/\$filter(dob gt 2000-01-01)")
        );
    }

    public function test_path_query_filter()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath."/\$filter(dob gt 2000-01-01)")
                ->filter("endswith(name, 'ta')")
        );
    }

    public function test_path_query_filter_search()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath."/\$filter(dob gt 2000-01-01)")
                ->filter("endswith(name, 'ta')")
                ->search('a')
        );
    }

    public function test_path_filter_no_argument()
    {
        $this->assertBadRequest(
            (new Request)
                ->path("\$filter(endswith(name, 'ta'))")
        );
    }

    public function test_path_query_filter_segment_and_param()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath."/\$filter(@ib)")
                ->query('@ib', 'dob gt 2000-01-01')
                ->filter("endswith(name, 'ta')")
        );
    }

    public function test_path_query_filter_segment_multiple()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'/$filter(@a)/$filter(@b)')
                ->query('@a', "dob gt 2000-01-01")
                ->query('@b', "endswith(name, 'ta')")
        );
    }

    public function test_path_query_filter_segment_multiple_count()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'/$filter(@a)/$filter(@b)/$count')
                ->text()
                ->query('@a', "dob gt 2000-01-01")
                ->query('@b', "endswith(name, 'ta')")
        );
    }

    public function test_path_query_filter_param_segment_multiple_paginated()
    {
        $this->assertPaginationSequence(
            (new Request)
                ->path($this->entitySetPath.'/$filter(@b)')
                ->filter("dob gt 2000-01-01")
                ->query('@b', "endswith(name, 'a')")
                ->top('1')
        );
    }

    public function test_filter_boolean_eq_true()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('chips eq true')
                ->select('name,chips')
        );
    }

    public function test_filter_boolean_ne_true()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('chips ne true')
                ->select('name,chips')
        );
    }

    public function test_filter_boolean_eq_false()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('chips eq false')
                ->select('name,chips')
        );
    }

    public function test_filter_boolean_ne_false()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('chips ne false')
                ->select('name,chips')
        );
    }

    public function test_filter_date_eq()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('dq eq 2001-02-02')
                ->select('name,dq')
        );
    }

    public function test_filter_date_gt()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('dq gt 2001-02-02')
                ->select('name,dq')
        );
    }

    public function test_filter_date_lt()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('dq lt 2001-02-02')
                ->select('name,dq')
        );
    }

    public function test_filter_date_le()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('dq le 2001-02-02')
                ->select('name,dq')
        );
    }

    public function test_filter_invalid_date()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('dq lt 1935-0x-')
        );
    }

    public function test_filter_datetime_eq()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('dob eq 2001-02-02T05:05:05Z')
                ->select($this->entitySetKey.',dob')
        );
    }

    public function test_filter_datetime_gt()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('dob gt 2001-02-02T05:05:05Z')
                ->select($this->entitySetKey.',dob')
        );
    }

    public function test_filter_datetime_lt()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('dob lt 2001-02-02T05:05:05Z')
                ->select($this->entitySetKey.',dob')
        );
    }

    public function test_filter_duration_eq()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('in_role eq P1DT0S')
        );
    }

    public function test_filter_duration_gt()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('in_role gt P1DT0S')
        );
    }

    public function test_filter_duration_lt()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('in_role lt P1DT0S')
        );
    }

    public function test_filter_string_eq()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter("name eq 'Beta'")
                ->select('name')
        );
    }

    public function test_filter_string_ne()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter("name ne 'Beta'")
                ->select('name')
        );
    }

    public function test_filter_string_gt()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter("name gt 'Beta'")
                ->select('name')
        );
    }

    public function test_filter_string_lt()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter("name lt 'Beta'")
                ->select('name')
        );
    }

    public function test_filter_time_eq()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('open_time eq 07:07:07')
                ->select('name,open_time')
        );
    }

    public function test_filter_time_gt()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('open_time gt 07:07:07')
                ->select('name,open_time')
        );
    }

    public function test_filter_time_lt()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->filter('open_time lt 07:07:07')
                ->select('name,open_time')
        );
    }

    public function test_filter_cast()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("substring(cast(dob, 'Edm.String'), 0, 6) eq '2000-0'")
                ->path($this->entitySetPath)
        );
    }

    public function test_filter_search()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->filter("(contains(tolower(cast(name, 'Edm.String')),'alpha')) or (contains(tolower(cast(dob, 'Edm.String')),'alpha'))")
                ->path($this->entitySetPath)
        );
    }
}
