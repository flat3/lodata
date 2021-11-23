<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Unit\Eloquent;

use Flat3\Lodata\Drivers\EloquentRepository;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Tests\Models\Airport;
use Flat3\Lodata\Tests\Models\Repository;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class Repository73Test extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->withFlightDatabase();
        $this->withFlightData();

        $op1 = new Operation('op1');
        $op1->setCallable([Airport::class, 'op1']);
        Lodata::add($op1);

        $op2 = new Operation('op2');
        $op2->setCallable([Airport::class, 'op2']);
        Lodata::add($op2);

        Lodata::discoverEloquentModel(Airport::class);
        $code = new EloquentRepository('code');
        $code->setCallable([Repository::class, 'code']);
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