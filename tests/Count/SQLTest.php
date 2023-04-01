<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Count;

use Flat3\Lodata\Tests\Drivers\WithSQLDriver;

/**
 * @group sql
 */
class SQLTest extends Count
{
    use WithSQLDriver;
}
