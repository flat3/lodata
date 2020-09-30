<?php

namespace Flat3\OData\Tests\Data;

use Exception;
use Flat3\OData\DataModel;
use Flat3\OData\Operation\Action;
use Flat3\OData\Operation\Function_;
use Flat3\OData\Type\String_;

trait ExampleDataModel
{
    public function withExampleDataModel(): void
    {
        try {
            /** @var DataModel $model */
            $model = app()->make(DataModel::class);

            $exampleFunction = new Function_('example', String_::class);
            $exampleFunction->setCallback(function () {
                return String_::factory('hello');
            });

            $exampleAction = new Action('example2', String_::class);
            $exampleAction->setCallback(function () {
                return String_::factory('hello');
            });

            $model->addResource($exampleFunction);
            $model->addResource($exampleAction);
        } catch (Exception $e) {
        }
    }
}
