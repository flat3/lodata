<?php

namespace Flat3\Lodata\Tests\Unit\Collection;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\CollectionEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;
use Illuminate\Foundation\Application;

class CollectionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $collection = collect([
            'alpha' => [
                'name' => 'Alpha',
                'age' => 4,
            ],
            'beta' => [
                'name' => 'Beta',
                'age' => 3,
            ],
            'gamma' => [
                'name' => 'Gamma',
                'age' => 2
            ],
            'delta' => [
                'name' => 'Delta',
            ],
            'epsilon' => [
                'name' => 'Epsilon',
                'age' => 2.4,
            ],
        ]);

        $entityType = new EntityType('example');
        $entityType->setKey(new DeclaredProperty('id', Type::string()));
        $entityType->addDeclaredProperty('name', Type::string());
        $entityType->getDeclaredProperty('name')->setSearchable();
        $entityType->addDeclaredProperty('age', Type::double());
        $entitySet = new CollectionEntitySet('examples', $entityType);
        $entitySet->setCollection($collection);

        Lodata::add($entitySet);
    }

    public function test_top()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$top', 2)
                ->path('/examples')
        );
    }

    public function test_skip()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$top', 2)
                ->query('$skip', 2)
                ->path('/examples')
        );
    }

    public function test_orderby()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$orderby', 'name desc')
                ->path('/examples')
        );
    }

    public function test_orderby_multiple()
    {
        $request = Request::factory()
            ->query('$orderby', 'name desc, age asc')
            ->path('/examples');

        if (version_compare(Application::VERSION, '8', '<')) {
            $this->assertNotImplemented($request);
        } else {
            $this->assertJsonResponse($request);
        }
    }

    public function test_search()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$search', 'l')
                ->path('/examples')
        );
    }

    public function test_search_2()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$search', 'lph or amm')
                ->path('/examples')
        );
    }

    public function test_search_3()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$search', 'a and m')
                ->path('/examples')
        );
    }

    public function test_search_4()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$search', 'not lph')
                ->path('/examples')
        );
    }

    public function test_filter()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "name eq 'Alpha'")
                ->path('/examples')
        );
    }

    public function test_filter_gt()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "age gt 3")
                ->path('/examples')
        );
    }

    public function test_filter_ge()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "age ge 3")
                ->path('/examples')
        );
    }

    public function test_filter_le()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "age le 3")
                ->path('/examples')
        );
    }

    public function test_filter_lt()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "age lt 3")
                ->path('/examples')
        );
    }

    public function test_filter_ne()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "age ne 3")
                ->path('/examples')
        );
    }

    public function test_filter_in()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "age in (2,3)")
                ->path('/examples')
        );
    }

    public function test_filter_eq_null()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "age eq null")
                ->path('/examples')
        );
    }

    public function test_filter_or()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "name eq 'Alpha' or name eq 'Gamma'")
                ->path('/examples')
        );
    }

    public function test_filter_startswith()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "startswith(name, 'Alph')")
                ->path('/examples')
        );
    }

    public function test_filter_substring_1()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "substring(name, 2) eq 'pha'")
                ->path('/examples')
        );
    }

    public function test_filter_substring_2()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "substring(name, 2, 1) eq 'p'")
                ->path('/examples')
        );
    }

    public function test_filter_not()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "not (name eq 'Alpha')")
                ->path('/examples')
        );
    }

    public function test_filter_contains()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "contains(name, 't')")
                ->path('/examples')
        );
    }

    public function test_filter_length()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "length(name) eq 4")
                ->path('/examples')
        );
    }

    public function test_filter_indexof()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "indexof(name, 'pha') eq 2")
                ->path('/examples')
        );
    }

    public function test_filter_round()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "round(age) eq 2")
                ->path('/examples')
        );
    }

    public function test_filter_ceiling()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "ceiling(age) eq 3")
                ->path('/examples')
        );
    }

    public function test_filter_floor()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "floor(age) eq 2")
                ->path('/examples')
        );
    }

    public function test_filter_tolower()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "tolower(name) eq 'epsilon'")
                ->path('/examples')
        );
    }

    public function test_filter_toupper()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "toupper(name) eq 'EPSILON'")
                ->path('/examples')
        );
    }

    public function test_filter_trim()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "trim(' a') eq 'a'")
                ->path('/examples')
        );
    }

    public function test_filter_matchespattern()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "matchesPattern(name, '^G')")
                ->path('/examples')
        );
    }

    public function test_filter_concat()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "name eq concat('Ga','mma')")
                ->path('/examples')
        );
    }

    public function test_filter_endswith()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "endswith(name, 'ta')")
                ->path('/examples')
        );
    }

    public function test_filter_add()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "age add 10 eq 13")
                ->path('/examples')
        );
    }

    public function test_filter_sub()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "age sub 10 eq -7")
                ->path('/examples')
        );
    }

    public function test_filter_div()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "age div 10 eq 0.3")
                ->path('/examples')
        );
    }

    public function test_filter_divby()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "age divby 10 eq 0.3")
                ->path('/examples')
        );
    }

    public function test_filter_mul()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "age mul 10 eq 30")
                ->path('/examples')
        );
    }

    public function test_filter_mod()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "age mod 3 eq 0")
                ->path('/examples')
        );
    }

    public function test_filter_endswith_or()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->query('$filter', "endswith(name, 'ta') or name eq 'Alpha'")
                ->path('/examples')
        );
    }
}