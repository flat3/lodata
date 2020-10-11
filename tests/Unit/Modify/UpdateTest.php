<?php

namespace Flat3\OData\Tests\Unit\Modify;

use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class UpdateTest extends TestCase
{
    public function test_update()
    {
        $this->withFlightModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->patch()
                ->body([
                    'origin' => 'ooo',
                ])
        );
    }
}