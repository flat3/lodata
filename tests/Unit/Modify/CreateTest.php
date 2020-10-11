<?php

namespace Flat3\OData\Tests\Unit\Modify;

use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class CreateTest extends TestCase
{
    public function test_create()
    {
        $this->withFlightModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->post()
                ->body([
                    'origin' => 'lhr',
                ])
        );
    }
}