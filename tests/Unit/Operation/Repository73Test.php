<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Unit\Operation;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation\Repository;
use Flat3\Lodata\Tests\Models\Airport;
use Flat3\Lodata\Tests\Models\Repository as RepositoryModel;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class Repository73Test extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->withFlightDatabase();
        $this->withFlightData();

        Lodata::discoverEloquentModel(Airport::class);
        $code = new Repository('code');
        $code->setCallable([RepositoryModel::class, 'code']);
        $code->setBindingParameterName('airport');
        Lodata::add($code);
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