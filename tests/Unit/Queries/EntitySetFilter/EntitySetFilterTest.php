<?php

namespace Flat3\OData\Tests\Unit\Queries\EntitySetFilter;

use Flat3\OData\Tests\Data\FlightModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class EntitySetFilterTest extends TestCase
{
    use FlightModel;

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
