<?php

/**
 * Copyright (c) 2018 Josias Montag
 * https://github.com/josiasmontag/laravel-redis-mock
 */

namespace Flat3\Lodata\Tests\Helpers;

use Illuminate\Redis\Connections\PredisConnection;

class MockPredisConnection extends PredisConnection
{
    public function pipeline(callable $callback = null)
    {
        $pipeline = $this->client()->pipeline();

        return is_null($callback) ? $pipeline : tap($pipeline, $callback)->exec();
    }
}
