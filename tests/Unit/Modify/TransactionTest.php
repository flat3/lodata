<?php

namespace Flat3\Lodata\Tests\Unit\Modify;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class TransactionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
        $this->captureDatabaseState();
    }

    public function tearDown(): void
    {
        $this->assertNoTransactionsInProgress();
        $this->assertDatabaseMatchesCapturedState();
        parent::tearDown();
    }

    public function test_create_deep_failed()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights')
                ->post()
                ->body([
                    'origin' => 'lhr',
                    'destination' => 'sfo',
                    'passengers' => [
                        [
                            'name' => 'Bob',
                        ],
                        [],
                    ],
                ]),
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}