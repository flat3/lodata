<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Pagination;

use Flat3\Lodata\Drivers\EloquentEntitySet;
use Flat3\Lodata\Tests\Drivers\WithEloquentDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\Laravel\Models\Pet;

/**
 * @group eloquent
 */
class EloquentTest extends Pagination
{
    use WithEloquentDriver;

    public static function chunkSizes(): array
    {
        return [[1], [10], [100]];
    }

    /**
     * @dataProvider chunkSizes
     */
    public function test_large($chunkSize)
    {
        $this->driverState = null;

        EloquentEntitySet::$chunkSize = $chunkSize;

        for ($i = 0; $i < 40; $i++) {
            (new Pet)->fill([
                'name' => 'rocket',
                'type' => 'dog',
            ])->save();
        }

        $this->assertPaginationSequence(
            (new Request)
                ->skip('2')
                ->top('15')
                ->orderby('id desc')
                ->filter("type eq 'dog'")
                ->count('true')
                ->path($this->petEntitySetPath)
        );

        $this->assertPaginationSequence(
            (new Request)
                ->top('15')
                ->orderby('id desc')
                ->filter("type eq 'dog'")
                ->count('true')
                ->path($this->petEntitySetPath)
        );

        $this->assertPaginationSequence(
            (new Request)
                ->skip('15')
                ->orderby('id desc')
                ->filter("type eq 'dog'")
                ->count('true')
                ->path($this->petEntitySetPath)
        );
    }
}
