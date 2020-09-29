<?php

namespace Flat3\OData\Tests\Unit\Protocol;

use Flat3\OData\Exception\Protocol\NotImplementedException;
use Flat3\OData\Tests\JsonDriver;
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

    public function test_error_response_body()
    {
        try {
            throw NotImplementedException::factory()
                ->code('test')
                ->message('test message')
                ->target('test target')
                ->details('test details')
                ->inner('inner error');
        } catch (NotImplementedException $e) {
            $response = $e->toResponse(new \Illuminate\Http\Request());
            $this->assertMatchesSnapshot($response->getContent(), new JsonDriver());
        }
    }
}
