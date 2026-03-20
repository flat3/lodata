<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Compute;

use Flat3\Lodata\Tests\Drivers\WithCSVDriver;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group csv
 */
#[Group('csv')]
class CSVTest extends Compute
{
    use WithCSVDriver;
}
