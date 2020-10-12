<?php

namespace Flat3\Lodata\Tests\Unit\Queries\EntitySetFilter;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class EntitySetFilterTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_path_filter()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path("/airports/\$filter(code eq 'lhr')")
        );
    }

    public function test_path_query_filter()
    {
        $this->markTestIncomplete();

        $this->assertJsonResponse(
            Request::factory()
                ->path("/airports/\$filter(is_big eq true)")
                ->query('$filter', "code eq 'lhr'")
        );
    }
}
