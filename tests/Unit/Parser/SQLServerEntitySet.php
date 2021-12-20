<?php

namespace Flat3\Lodata\Tests\Unit\Parser;

use Flat3\Lodata\Drivers\SQLEntitySet;

class SQLServerEntitySet extends SQLEntitySet
{
    public function getDriver(): string
    {
        return SQLEntitySet::SQLServer;
    }
}