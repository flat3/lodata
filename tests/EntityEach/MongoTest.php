<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntityEach;

use Flat3\Lodata\Tests\Drivers\WithMongoDriver;

/**
 * @group mongo
 */
class MongoTest extends Database
{
    use WithMongoDriver;
}
