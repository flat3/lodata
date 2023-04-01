<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\OrderBy;

use Flat3\Lodata\Tests\Drivers\WithCSVDriver;

/**
 * @group csv
 */
class CSVTest extends OrderBy
{
    use WithCSVDriver;
}
