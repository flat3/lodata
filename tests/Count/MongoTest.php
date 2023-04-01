<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Count;

use Flat3\Lodata\Tests\Drivers\WithMongoDriver;

/**
 * @group mongo
 */
class MongoTest extends Count
{
    use WithMongoDriver;
}
