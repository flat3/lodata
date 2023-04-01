<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Search;

use Flat3\Lodata\Tests\Drivers\WithSQLDriver;

/**
 * @group sql
 */
class SQLTest extends Search
{
    use WithSQLDriver;
}
