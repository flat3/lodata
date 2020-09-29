<?php

namespace Flat3\OData\Tests\Data;

use Exception;
use Flat3\OData\DataModel;
use Flat3\OData\Operation\Function_;
use Flat3\OData\Type\String_;

trait ExampleDataModel
{
    public function withExampleDataModel(): void
    {
        try {
            /** @var DataModel $model */
            $model = app()->make(DataModel::class);

            $callback = new Function_('example', String_::class);
            $callback->setCallback(function () {
                return String_::factory('hello');
            });

            $model->addResource($callback);
        } catch (Exception $e) {
        }
    }
}
