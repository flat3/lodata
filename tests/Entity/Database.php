<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Entity;

use Flat3\Lodata\Tests\Helpers\Request;

abstract class Database extends Entity
{
    public function test_read_alternative_key()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->airportEntitySetPath."(code='lhr')")
        );
    }
}
