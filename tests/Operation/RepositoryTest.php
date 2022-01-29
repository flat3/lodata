<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Operation;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\Laravel\Models\Airport;
use Flat3\Lodata\Tests\Laravel\Models\Repository;
use Flat3\Lodata\Tests\TestCase;

class RepositoryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped();
        }

        Lodata::discover(Airport::class);
        Lodata::discover(Repository::class);

        foreach ($this->getAirportSeed() as $record) {
            (new Airport)->newInstance($record)->save();
        }
    }

    public function test_metadata()
    {
        $this->assertMetadataSnapshot();
    }

    public function test_code()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/Airports/1/code')
        );
    }

    public function test_code_args()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/Airports/1/code(suffix='here')")
        );
    }
}