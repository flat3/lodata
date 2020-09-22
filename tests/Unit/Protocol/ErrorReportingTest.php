<?php

namespace Flat3\OData\Tests\Unit\Protocol;

use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class ErrorReportingTest extends TestCase
{
    public function test_error_reporting()
    {
        $this->withExceptionHandling();

        $response = $this->req(
            Request::factory()
                ->query('$format', 'xml')
        );

        $this->assertMatchesJsonSnapshot($response->getContent());
    }
}
