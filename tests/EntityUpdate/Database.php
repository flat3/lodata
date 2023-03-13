<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntityUpdate;

use Flat3\Lodata\Tests\Helpers\Request;

abstract class Database extends EntityUpdate
{
    public function test_update_rejects_null_properties()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entityPath)
                ->patch()
                ->body([
                    'name' => null,
                ])
        );
    }
}
