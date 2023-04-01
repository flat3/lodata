<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntitySetCreate;

use Flat3\Lodata\Tests\Drivers\WithEloquentDriver;

/**
 * @group eloquent
 */
class EloquentTest extends EntitySetCreate
{
    use WithEloquentDriver;
}
