<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Unit\Operation;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Models\Airport;
use Flat3\Lodata\Tests\Models\Repository;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class RepositoryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped();
        }

        $this->withFlightDatabase();
        $this->withFlightData();

        Lodata::discover(Airport::class);
        Lodata::discover(Repository::class);
    }

    public function test_metadata()
    {
        $this->assertMetadataDocuments();
    }

    public function test_code()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/Airports/1/code')
        );
    }

    public function test_code_args()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path("/Airports/1/code(suffix='here')")
        );
    }
}