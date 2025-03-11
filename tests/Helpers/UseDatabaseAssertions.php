<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Helpers;

use Closure;
use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\Tests\Laravel\Models\Airport;
use Flat3\Lodata\Tests\Laravel\Models\Cast;
use Flat3\Lodata\Tests\Laravel\Models\Country;
use Flat3\Lodata\Tests\Laravel\Models\Flight;
use Flat3\Lodata\Tests\Laravel\Models\Name;
use Flat3\Lodata\Tests\Laravel\Models\Passenger;
use Flat3\Lodata\Tests\Laravel\Models\Pet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PDOException;

trait UseDatabaseAssertions
{
    protected $driverSpecificSnapshots = false;

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom($this->migrations);
    }

    protected function setUpDatabaseSnapshots()
    {
        $this->driverSpecificSnapshots = false;
    }

    protected function useDriverSpecificSnapshots(array $drivers = [])
    {
        $driver = $this->getConnection()->getDriverName();

        if ($driver === SQLEntitySet::SQLite) {
            return;
        }

        if (!$drivers || in_array($driver, $drivers)) {
            $this->driverSpecificSnapshots = true;
        }
    }

    protected function captureDatabase(): array
    {
        $db = [];

        /** @var Model $modelClass */
        foreach ([
                     Airport::class,
                     Cast::class,
                     Country::class,
                     Flight::class,
                     Name::class,
                     Passenger::class,
                     Pet::class
                 ] as $modelClass) {
            try {
                /** @var Model $model */
                $model = new $modelClass;
                $db[$model->getTable()] = array_values($model->all()->sortBy($model->getKey())->toArray());
            } catch (QueryException $e) {
            }
        }

        return $db;
    }

    protected function assertNoTransactionsInProgress()
    {
        try {
            $this->getConnection('testing')->beginTransaction();
            $this->getConnection('testing')->rollBack();
        } catch (PDOException $e) {
            $this->fail('A transaction was in progress');
        }
    }


    // https://github.com/mattiasgeniar/phpunit-query-count-assertions/blob/master/src/AssertsQueryCounts.php
    protected function assertNoQueriesExecuted(?Closure $closure = null): void
    {
        if ($closure) {
            self::trackQueries();

            $closure();
        }

        $this->assertQueryCountMatches(0);

        if ($closure) {
            DB::flushQueryLog();
        }
    }

    protected function assertQueryCountMatches(int $count, ?Closure $closure = null): void
    {
        if ($closure) {
            self::trackQueries();

            $closure();
        }

        $this->assertEquals($count, self::getQueryCount());

        if ($closure) {
            DB::flushQueryLog();
        }
    }

    protected function assertQueryCountLessThan(int $count, ?Closure $closure = null): void
    {
        if ($closure) {
            self::trackQueries();

            $closure();
        }

        $this->assertLessThan($count, self::getQueryCount());

        if ($closure) {
            DB::flushQueryLog();
        }
    }

    protected function assertQueryCountGreaterThan(int $count, ?Closure $closure = null): void
    {
        if ($closure) {
            self::trackQueries();

            $closure();
        }

        $this->assertGreaterThan($count, self::getQueryCount());

        if ($closure) {
            DB::flushQueryLog();
        }
    }

    protected static function trackQueries(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
    }

    protected static function getQueriesExecuted(): array
    {
        return DB::getQueryLog();
    }

    protected static function getQueryCount(): int
    {
        return count(self::getQueriesExecuted());
    }
}
