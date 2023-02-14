<?php

/**
 * Copyright (c) 2018 Josias Montag
 * https://github.com/josiasmontag/laravel-redis-mock
 */

namespace Flat3\Lodata\Tests\Helpers;

use Illuminate\Redis\Connectors\PredisConnector;
use Illuminate\Support\Arr;
use M6Web\Component\RedisMock\RedisMockFactory;

class MockPredisConnector extends PredisConnector
{
    public function connect(array $config, array $options)
    {
        $formattedOptions = array_merge(
            ['timeout' => 10.0], $options, Arr::pull($config, 'options', [])
        );


        $factory = new RedisMockFactory();
        $redisMockClass = $factory->getAdapter('Predis\Client', true);

        return new MockPredisConnection(new $redisMockClass($config, $formattedOptions));
    }

    public function connectToCluster(array $config, array $clusterOptions, array $options)
    {
        $clusterSpecificOptions = Arr::pull($config, 'options', []);

        $factory = new RedisMockFactory();
        $redisMockClass = $factory->getAdapter('Predis\Client', true);

        return new MockPredisConnection(new $redisMockClass(array_values($config), array_merge(
            $options, $clusterOptions, $clusterSpecificOptions
        )));
    }

}
