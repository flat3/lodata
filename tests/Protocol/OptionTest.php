<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

class OptionTest extends TestCase
{
    use WithNumericCollectionDriver;

    public function test_invalid_query_option()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath)
                ->query('$hello', 'origin')
        );
    }

    public function test_valid_nonstandard_query_option()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->query('hello', 'origin')
        );
    }

    public function test_noprefix_query_option()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->query('select', 'name')
        );
    }
}