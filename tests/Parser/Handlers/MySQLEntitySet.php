<?php

namespace Flat3\Lodata\Tests\Parser\Handlers;

class MySQLEntitySet extends SQLEntitySet
{
    public function getDriver(): string
    {
        return SQLEntitySet::MySQL;
    }
}