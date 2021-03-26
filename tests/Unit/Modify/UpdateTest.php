<?php

namespace Flat3\Lodata\Tests\Unit\Modify;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class UpdateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function tearDown(): void
    {
        $this->assertDatabaseSnapshot();
        parent::tearDown();
    }

    public function test_update()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->patch()
                ->body([
                    'origin' => 'ooo',
                ])
        );

        $this->assertDatabaseSnapshot();
    }

    public function test_update_put()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->put()
                ->body([
                    'origin' => 'ooo',
                ])
        );
    }

    public function test_update_ref()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)/$ref')
                ->patch()
                ->body([
                    'origin' => 'ooo',
                ])
        );
    }
}