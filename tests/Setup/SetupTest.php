<?php

namespace Flat3\Lodata\Tests\Setup;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;

class SetupTest extends TestCase
{
    public function test_invalid_unqualified_identifier()
    {
        $this->expectException(InternalServerErrorException::class);
        new SQLEntitySet('3a', new EntityType('a3'));
    }

    public function test_invalid_qualified_identifier()
    {
        $this->expectException(InternalServerErrorException::class);
        new SQLEntitySet('example.3a', new EntityType('a3'));
    }

    public function test_invalid_name()
    {
        $this->expectException(InternalServerErrorException::class);
        new DeclaredProperty('3a', Type::int32());
    }
}