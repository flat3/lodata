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
            (new Request)
                ->path("/airports/\$filter(code eq 'lhr')")
        );
    }

    public function test_path_query_filter()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path("/airports/\$filter(is_big eq true)")
                ->query('$filter', "code eq 'lhr'")
        );
    }

    public function test_path_filter_no_argument()
    {
        $this->assertBadRequest(
            (new Request)
                ->path("\$filter(code eq 'lhr')")
        );
    }
}
