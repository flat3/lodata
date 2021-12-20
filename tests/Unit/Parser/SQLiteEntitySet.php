<?php

namespace Flat3\Lodata\Tests\Unit\Parser;

use Flat3\Lodata\Drivers\SQLEntitySet;

class SQLiteEntitySet extends SQLEntitySet
{
    public function getDriver(): string
    {
        return SQLEntitySet::SQLite;
    }
}