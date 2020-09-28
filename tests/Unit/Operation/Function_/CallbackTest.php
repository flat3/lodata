<?php

namespace Flat3\OData\Tests\Unit\Operation\Function_;

use Flat3\OData\DataModel;
use Flat3\OData\Operation\Function_\Callback;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;
use Flat3\OData\Type\String_;

class CallbackTest extends TestCase
{
    public function test_callback()
    {
        /** @var DataModel $model */
        $model = app()->make(DataModel::class);

        $callback = new Callback('example', String_::class);
        $callback->setCallback(function () {
            return String_::factory('hello');
        });

        $model->resource($callback);

        $this->assertJsonResponse(
            Request::factory()
                ->path('/example()')
        );
    }
}