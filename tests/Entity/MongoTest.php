<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Entity;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Drivers\WithMongoDriver;
use Flat3\Lodata\Tests\Helpers\Request;

/**
 * @group mongo
 */
class MongoTest extends Entity
{
    use WithMongoDriver;

    public function test_read_alternative_key()
    {
        Lodata::getEntityType('passenger')->getDeclaredProperty('name')->setAlternativeKey();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath."(name='Alpha')")
        );
    }
}