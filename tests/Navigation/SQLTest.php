<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Navigation;

use Flat3\Lodata\Tests\Drivers\WithSQLDriver;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group sql
 */
#[Group('sql')]
class SQLTest extends Navigation
{
    use WithSQLDriver;
}
