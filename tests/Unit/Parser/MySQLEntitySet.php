<?php

namespace Flat3\Lodata\Tests\Unit\Parser;

use Flat3\Lodata\Drivers\SQLEntitySet;

class MySQLEntitySet extends SQLEntitySet
{
    public function getDriver(): string
    {
        return 'mysql';
    }
}