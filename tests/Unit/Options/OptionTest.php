<?php

namespace Flat3\Lodata\Tests\Unit\Options;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class OptionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_invalid_query_option()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/flights')
                ->query('$hello', 'origin')
        );
    }

    public function test_valid_nonstandard_query_option()
    {
        $this->assertMetadataResponse(
            Request::factory()
                ->path('/flights')
                ->query('hello', 'origin')
        );
    }

    public function test_noprefix_query_option()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('select', 'origin')
        );
    }
}