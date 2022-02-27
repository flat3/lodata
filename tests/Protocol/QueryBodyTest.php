<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MediaType;

class QueryBodyTest extends TestCase
{
    use WithNumericCollectionDriver;

    public function test_wrong_content_type()
    {
        $this->assertNotAcceptable(
            (new Request)
                ->path($this->entitySetPath.'/$query')
                ->post()
        );
    }

    public function test_wrong_method()
    {
        $this->assertMethodNotAllowed(
            (new Request)
                ->header('content-type', MediaType::text)
                ->path($this->entitySetPath.'/$query')
        );
    }

    public function test_query()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'/$query')
                ->post()
                ->header('content-type', MediaType::text)
                ->body(http_build_query([
                    '$count' => 'true',
                    '$filter' => "name eq 'Alpha'",
                ]))
        );
    }
}