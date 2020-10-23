<?php

namespace Flat3\Lodata\Tests\Unit\Setup;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Tests\TestCase;

class SetupTest extends TestCase
{
    public function test_invalid_unqualified_identifier()
    {
        try {
            new SQLEntitySet('3a', new EntityType('a3'));
        } catch (ProtocolException $e) {
            $this->assertProtocolExceptionSnapshot($e);
        }
    }

    public function test_invalid_qualified_identifier()
    {
        try {
            new SQLEntitySet('example.3a', new EntityType('a3'));
        } catch (ProtocolException $e) {
            $this->assertProtocolExceptionSnapshot($e);
        }
    }

    public function test_invalid_name()
    {
        try {
            new DeclaredProperty('3a', PrimitiveType::int32());
        } catch (ProtocolException $e) {
            $this->assertProtocolExceptionSnapshot($e);
        }
    }
}