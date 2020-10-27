<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Illuminate\Support\Env;

class PreviewTest extends TestCase
{
    public function setUp(): void
    {
        Env::getRepository()->set('LODATA_PREVIEW', 1);
        parent::setUp();
        $this->gateMock->andReturnFalse();
        $this->withFlightModel();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Env::getRepository()->clear('LODATA_PREVIEW');
    }

    public function test_disables_auth()
    {
        $this->withMiddleware();

        $this->assertMetadataResponse(
            Request::factory()
        );
    }

    public function test_disables_gates()
    {
        $this->withMiddleware();

        $this->assertMetadataResponse(
            Request::factory()
                ->path('/flights')
        );
    }
}

