<?php

namespace Flat3\OData\Tests\Unit\Modify;

use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class DeleteTest extends TestCase
{
    public function test_delete()
    {
        $this->withFlightModel();

        $this->assertNoContent(
            Request::factory()
                ->path('/flights(1)')
                ->delete()
        );
    }
}