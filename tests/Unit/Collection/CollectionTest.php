<?php

namespace Flat3\Lodata\Tests\Unit\Collection;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\CollectionEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;

class CollectionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $collection = collect([
            'alpha' => [
                'name' => 'Alpha',
                'age' => 4,
                'dob' => '2000-01-01 04:04:04',
            ],
            'beta' => [
                'name' => 'Beta',
                'age' => 3,
                'dob' => '2001-02-02 05:05:05',
            ],
            'gamma' => [
                'name' => 'Gamma',
                'age' => 2,
                'dob' => '2002-03-03 06:06:06',
            ],
            'delta' => [
                'name' => 'Delta',
            ],
            'epsilon' => [
                'name' => 'Epsilon',
                'age' => 2.4,
                'dob' => '2003-04-04 07:07:07',
            ],
        ]);

        $entityType = new EntityType('example');
        $entityType->setKey(new DeclaredProperty('id', Type::string()));
        $entityType->addDeclaredProperty('name', Type::string());
        $entityType->getDeclaredProperty('name')->setSearchable();
        $entityType->addDeclaredProperty('age', Type::double());
        $entityType->addDeclaredProperty('dob', Type::datetimeoffset());
        $entitySet = new CollectionEntitySet('examples', $entityType);
        $entitySet->setCollection($collection);

        Lodata::add($entitySet);
    }

    public function test_top()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$top', 2)
                ->path('/examples')
        );
    }

    public function test_skip()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$top', 2)
                ->query('$skip', 2)
                ->path('/examples')
        );
    }

    public function test_orderby()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$orderby', 'name desc')
                ->path('/examples')
        );
    }

    public function test_orderby_multiple()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$orderby', 'name desc, age asc')
                ->path('/examples')
        );
    }

    public function test_search()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$search', 'l')
                ->path('/examples')
        );
    }

    public function test_search_2()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$search', 'lph or amm')
                ->path('/examples')
        );
    }

    public function test_search_3()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$search', 'a and m')
                ->path('/examples')
        );
    }

    public function test_search_4()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$search', 'not lph')
                ->path('/examples')
        );
    }

    public function test_filter()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "name eq 'Alpha'")
                ->path('/examples')
        );
    }

    public function test_filter_gt()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "age gt 3")
                ->path('/examples')
        );
    }

    public function test_filter_ge()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "age ge 3")
                ->path('/examples')
        );
    }

    public function test_filter_le()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "age le 3")
                ->path('/examples')
        );
    }

    public function test_filter_lt()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "age lt 3")
                ->path('/examples')
        );
    }

    public function test_filter_ne()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "age ne 3")
                ->path('/examples')
        );
    }

    public function test_filter_in()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "age in (2,3)")
                ->path('/examples')
        );
    }

    public function test_filter_eq_null()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "age eq null")
                ->path('/examples')
        );
    }

    public function test_filter_or()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "name eq 'Alpha' or name eq 'Gamma'")
                ->path('/examples')
        );
    }

    public function test_filter_startswith()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "startswith(name, 'Alph')")
                ->path('/examples')
        );
    }

    public function test_filter_substring_1()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "substring(name, 2) eq 'pha'")
                ->path('/examples')
        );
    }

    public function test_filter_substring_2()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "substring(name, 2, 1) eq 'p'")
                ->path('/examples')
        );
    }

    public function test_filter_not()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "not (name eq 'Alpha')")
                ->path('/examples')
        );
    }

    public function test_filter_contains()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "contains(name, 't')")
                ->path('/examples')
        );
    }

    public function test_filter_length()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "length(name) eq 4")
                ->path('/examples')
        );
    }

    public function test_filter_indexof()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "indexof(name, 'pha') eq 2")
                ->path('/examples')
        );
    }

    public function test_filter_round()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "round(age) eq 2")
                ->path('/examples')
        );
    }

    public function test_filter_ceiling()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "ceiling(age) eq 3")
                ->path('/examples')
        );
    }

    public function test_filter_floor()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "floor(age) eq 2")
                ->path('/examples')
        );
    }

    public function test_filter_tolower()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "tolower(name) eq 'epsilon'")
                ->path('/examples')
        );
    }

    public function test_filter_toupper()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "toupper(name) eq 'EPSILON'")
                ->path('/examples')
        );
    }

    public function test_filter_trim()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "trim(' a') eq 'a'")
                ->path('/examples')
        );
    }

    public function test_filter_matchespattern()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "matchesPattern(name, '^G')")
                ->path('/examples')
        );
    }

    public function test_filter_concat()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "name eq concat('Ga','mma')")
                ->path('/examples')
        );
    }

    public function test_filter_endswith()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "endswith(name, 'ta')")
                ->path('/examples')
        );
    }

    public function test_filter_add()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "age add 10 eq 13")
                ->path('/examples')
        );
    }

    public function test_filter_sub()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "age sub 10 eq -7")
                ->path('/examples')
        );
    }

    public function test_filter_div()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "age div 2 eq 1.2")
                ->path('/examples')
        );
    }

    public function test_filter_divby()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "age divby 10 eq 0.3")
                ->path('/examples')
        );
    }

    public function test_filter_mul()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "age mul 10 eq 30")
                ->path('/examples')
        );
    }

    public function test_filter_mod()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "age mod 3 eq 0")
                ->path('/examples')
        );
    }

    public function test_filter_endswith_or()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "endswith(name, 'ta') or name eq 'Alpha'")
                ->path('/examples')
        );
    }

    public function test_filter_day()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "day(dob) eq 4")
                ->path('/examples')
        );
    }

    public function test_filter_date()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "date(dob) eq 2000-01-01")
                ->path('/examples')
        );
    }

    public function test_filter_hour()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "hour(dob) eq 1")
                ->path('/examples')
        );
    }

    public function test_filter_minute()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "minute(dob) eq 1")
                ->path('/examples')
        );
    }

    public function test_filter_month()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "month(dob) eq 4")
                ->path('/examples')
        );
    }

    public function test_filter_second()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "second(dob) eq 4")
                ->path('/examples')
        );
    }

    public function test_filter_time()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "time(dob) eq 06:06:06")
                ->path('/examples')
        );
    }

    public function test_filter_year()
    {
        $this->assertJsonResponse(
            (new Request)
                ->query('$filter', "year(dob) eq 2000")
                ->path('/examples')
        );
    }
}