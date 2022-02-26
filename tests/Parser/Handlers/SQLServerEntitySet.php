<?php

namespace Flat3\Lodata\Tests\Parser\Handlers;

class SQLServerEntitySet extends SQLEntitySet
{
    public function getDriver(): string
    {
        return SQLEntitySet::SQLServer;
    }
}