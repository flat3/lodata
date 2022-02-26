<?php

namespace Flat3\Lodata\Tests\Parser\Handlers;

class SQLiteEntitySet extends SQLEntitySet
{
    public function getDriver(): string
    {
        return SQLEntitySet::SQLite;
    }
}