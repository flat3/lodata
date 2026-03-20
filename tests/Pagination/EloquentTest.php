<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Pagination;

use Flat3\Lodata\Drivers\EloquentEntitySet;
use Flat3\Lodata\Tests\Drivers\WithEloquentDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\Laravel\Models\Pet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group eloquent
 */
#[Group('eloquent')]
class EloquentTest extends Pagination
{
    use WithEloquentDriver;

    public static function chunkSizes(): array
    {
        return [[1], [10], [40], [100]];
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->driverState = null;

        Pet::truncate(); // @phpstan-ignore-line

        for ($i = 0; $i < 40; $i++) {
            (new Pet)->fill([
                'name' => 'rocket',
                'type' => 'dog',
            ])->save();
        }

        config(['lodata.pagination.default' => null]);
    }

    public function tearDown(): void
    {
        EloquentEntitySet::$chunkSize = 1000;
        parent::tearDown();
    }

    /**
     * @dataProvider chunkSizes
     */
    #[DataProvider('chunkSizes')]
    public function test_chunk_standard($chunkSize)
    {
        EloquentEntitySet::$chunkSize = $chunkSize;

        $this->assertPaginationSequence(
            (new Request)
                ->orderby('id asc')
                ->path($this->petEntitySetPath),
            PHP_INT_MAX, '@nextLink', 40,
        );
    }

    /**
     * @dataProvider chunkSizes
     */
    #[DataProvider('chunkSizes')]
    public function test_chunk_skip_top($chunkSize)
    {
        EloquentEntitySet::$chunkSize = $chunkSize;

        $this->assertPaginationSequence(
            (new Request)
                ->skip('2')
                ->top('15')
                ->orderby('id asc')
                ->filter("type eq 'dog'")
                ->count('true')
                ->path($this->petEntitySetPath),
            PHP_INT_MAX, '@nextLink', 40-2,
        );
    }

    /**
     * @dataProvider chunkSizes
     */
    #[DataProvider('chunkSizes')]
    public function test_chunk_top($chunkSize)
    {
        EloquentEntitySet::$chunkSize = $chunkSize;

        $this->assertPaginationSequence(
            (new Request)
                ->top('15')
                ->orderby('id asc')
                ->filter("type eq 'dog'")
                ->count('true')
                ->path($this->petEntitySetPath),
            PHP_INT_MAX, '@nextLink', 40,
        );
    }

    /**
     * @dataProvider chunkSizes
     */
    #[DataProvider('chunkSizes')]
    public function test_chunk_skip($chunkSize)
    {
        EloquentEntitySet::$chunkSize = $chunkSize;

        $this->assertPaginationSequence(
            (new Request)
                ->skip('15')
                ->orderby('id asc')
                ->filter("type eq 'dog'")
                ->count('true')
                ->path($this->petEntitySetPath),
            PHP_INT_MAX, '@nextLink', 40 - 15,
        );
    }
}
