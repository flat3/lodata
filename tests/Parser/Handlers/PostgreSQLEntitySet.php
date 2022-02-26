<?php

namespace Flat3\Lodata\Tests\Parser\Handlers;

class PostgreSQLEntitySet extends SQLEntitySet
{
    public function getDriver(): string
    {
        return SQLEntitySet::PostgreSQL;
    }
}