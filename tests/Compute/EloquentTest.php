<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Compute;

use Flat3\Lodata\Tests\Drivers\WithEloquentDriver;

/**
 * @group eloquent
 */
class EloquentTest extends Compute
{
    use WithEloquentDriver;
}
