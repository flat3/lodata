<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Pagination;

use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\Tests\Drivers\WithEloquentDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Illuminate\Support\Facades\DB;

class EloquentTest extends PaginationTest
{
    use WithEloquentDriver;

    public function test_large()
    {
        if ($this->getConnection()->getDriverName() !== SQLEntitySet::PostgreSQL) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $this->driverState = null;

        DB::statement('delete from pets');
        DB::statement('ALTER SEQUENCE pets_id_seq RESTART WITH 1');
        DB::statement('insert into pets(name) select generate_series(1,10000)');

        $this->assertPaginationSequence(
            (new Request)
                ->select('id')
                ->path($this->petEntitySetPath)
                ->top('2000')
        );
    }
}