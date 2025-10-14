<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Pagination;

use Flat3\Lodata\Drivers\EloquentEntitySet;
use Flat3\Lodata\Facades\Lodata;
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
            PHP_INT_MAX, '@nextLink', 40 - 2,
        );
    }

    /**
     * @dataProvider chunkSizes
     */
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

    public function test_cursor()
    {
        Lodata::getEntitySet('Pets')->useTokenPagination();

        $this->assertPaginationSequence(
            (new Request)
                ->top('5')
                ->count('true')
                ->path($this->petEntitySetPath),
            PHP_INT_MAX, '@nextLink', 40,
        );
    }

    public function test_cursor_2()
    {
        Lodata::getEntitySet('Pets')->useTokenPagination();

        $this->assertPaginationSequence(
            (new Request)
                ->top('7')
                ->count('true')
                ->path($this->petEntitySetPath),
            PHP_INT_MAX, '@nextLink', 40,
        );
    }

    public function test_cursor_filter()
    {
        Lodata::getEntitySet('Pets')->useTokenPagination();

        $this->assertPaginationSequence(
            (new Request)
                ->top('5')
                ->orderby('id asc')
                ->filter("type eq 'dog'")
                ->count('true')
                ->path($this->petEntitySetPath),
            PHP_INT_MAX, '@nextLink', 40,
        );
    }

    public function test_cursor_multiple_orders()
    {
        Lodata::getEntitySet('Pets')->useTokenPagination();

        $this->assertPaginationSequence(
            (new Request)
                ->top('5')
                ->orderby('id desc, type asc')
                ->count('true')
                ->path($this->petEntitySetPath),
            PHP_INT_MAX, '@nextLink', 40,
        );
    }

    public function test_bad_cursor()
    {
        Lodata::getEntitySet('Pets')->useTokenPagination();

        $this->assertBadRequest(
            (new Request)
                ->top('5')
                ->skiptoken('broken')
                ->count('true')
                ->path($this->petEntitySetPath),
        );
    }

    public function test_cursor_no_top()
    {
        Lodata::getEntitySet('Pets')->useTokenPagination();

        $this->assertPaginationSequence(
            (new Request)
                ->count('true')
                ->path($this->petEntitySetPath),
            PHP_INT_MAX, '@nextLink', 40,
        );
    }
}
