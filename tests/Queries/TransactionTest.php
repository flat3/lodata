<?php

namespace Flat3\Lodata\Tests\Queries;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Drivers\WithSQLDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

/**
 * @group sql
 */
class TransactionTest extends TestCase
{
    use WithSQLDriver;

    public function test_create_deep_failed()
    {
        $this->useDriverSpecificSnapshots();

        $this->keepDriverState();
        Lodata::getEntityType('passenger')->getDeclaredProperty('name')->setNullable(true);

        $this->assertInternalServerError(
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
                ])
        );

        $this->assertNoTransactionsInProgress();
        $this->assertDriverStateUnchanged();
    }
}