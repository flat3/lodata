<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntityEach;

use Flat3\Lodata\Tests\Drivers\WithEloquentDriver;

/**
 * @group eloquent
 */
class EloquentTest extends Database
{
    use WithEloquentDriver;
}
