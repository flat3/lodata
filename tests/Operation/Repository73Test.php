<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Operation;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation\Repository;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\Laravel\Models\Airport;
use Flat3\Lodata\Tests\Laravel\Models\Repository as RepositoryModel;
use Flat3\Lodata\Tests\TestCase;

/**
 * @requires PHP < 7.4
 */
class Repository73Test extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Lodata::discoverEloquentModel(Airport::class);
        $code = new Repository('code');
        $code->setCallable([RepositoryModel::class, 'code']);
        $code->setBindingParameterName('airport');
        Lodata::add($code);

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