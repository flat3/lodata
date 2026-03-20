<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\OrderBy;

use Flat3\Lodata\Tests\Drivers\WithMongoDriver;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group mongo
 */
#[Group('mongo')]
class MongoTest extends OrderBy
{
    use WithMongoDriver;
}
