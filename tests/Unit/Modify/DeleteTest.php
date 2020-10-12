<?php

namespace Flat3\Lodata\Tests\Unit\Modify;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

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

    public function test_delete_not_found()
    {
        $this->withFlightModel();

        $this->assertNoContent(
            Request::factory()
                ->path('/flights(999)')
                ->delete()
        );
    }
}