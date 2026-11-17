<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntityEach;

use Flat3\Lodata\Tests\Drivers\WithMongoDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group mongo
 */
#[Group('mongo')]
class MongoTest extends Database
{
    use WithMongoDriver;

    public function test_delete_each_with_top()
    {
        $this->assertNoContent(
            (new Request)
                ->path($this->entitySetPath.'/$each')
                ->query('$top', '2')
                ->delete()
        );

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
        );
    }
}
