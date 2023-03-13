<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Search;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

abstract class Search extends TestCase
{
    public function test_search()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->search('l')
                ->path($this->entitySetPath)
        );
    }

    public function test_search_2()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->search('lph or amm')
                ->path($this->entitySetPath)
        );
    }

    public function test_search_3()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->search('a and m')
                ->path($this->entitySetPath)
        );
    }

    public function test_search_4()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->search('not lph')
                ->path($this->entitySetPath)
        );
    }

    public function test_count_uses_search()
    {
        $this->assertTextResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'/$count')
                ->text()
                ->search('a')
        );
    }

    public function test_search_no_searchable_properties()
    {
        Lodata::getEntitySet($this->entitySet)->getType()->getDeclaredProperty('name')->setSearchable(false);

        $this->assertInternalServerError(
            (new Request)
                ->path($this->entitySetPath)
                ->search('sfo')
        );
    }

    public function test_search_not()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->search('NOT pha')
        );
    }

    public function test_search_or()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->search('Bet OR Alp')
        );
    }

    public function test_search_and()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->search('Be AND ta')
        );
    }

    public function test_search_invalid()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath)
                ->search('Bet AND ta OR')
        );
    }

    public function test_search_quote()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->search('"ta "')
        );
    }

    public function test_search_paren()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->search('(Be OR ha) OR Gam')
        );
    }
}
