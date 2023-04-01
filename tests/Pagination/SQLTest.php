<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Pagination;

use Flat3\Lodata\Tests\Drivers\WithSQLDriver;

/**
 * @group sql
 */
class SQLTest extends Pagination
{
    use WithSQLDriver;
}
