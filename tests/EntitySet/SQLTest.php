<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntitySet;

use Flat3\Lodata\Tests\Drivers\WithSQLDriver;

/**
 * @group sql
 */
class SQLTest extends EntitySet
{
    use WithSQLDriver;
}
