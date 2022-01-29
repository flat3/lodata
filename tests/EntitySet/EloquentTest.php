<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntitySet;

use Flat3\Lodata\Drivers\EloquentEntitySet;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Drivers\WithEloquentDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\Laravel\Models\Airport;
use Illuminate\Database\Eloquent\Builder;

class EloquentTest extends EntitySetTest
{
    use WithEloquentDriver;

    public function test_scope()
    {
        $scoped = new class(Airport::class) extends EloquentEntitySet {
            public function __construct(string $model)
            {
                parent::__construct($model);

                $this->setIdentifier('Scoped');
            }

            public function getBuilder(): Builder
            {
                $builder = parent::getBuilder();
                return $builder->modern();
            }
        };

        Lodata::add($scoped);

        $scoped->discoverProperties();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/Scoped')
        );
    }

    public function test_op1()
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped();
        }

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/Airports/1/op1')
        );
    }

    public function test_op2()
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped();
        }

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/Airports/1/op2(prefix='o')")
        );
    }
}