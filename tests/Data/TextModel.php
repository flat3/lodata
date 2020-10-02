<?php

namespace Flat3\OData\Tests\Data;

use Exception;
use Flat3\OData\Entity;
use Flat3\OData\ODataModel;
use Flat3\OData\Property;
use Flat3\OData\Resource\EntitySet;
use Flat3\OData\Type;

trait TextModel
{
    public function withTextModel(): void
    {
        try {
            ODataModel::add(
                new class(
                    'texts',
                    ODataModel::entitytype('text')
                        ->addProperty(new Property\Declared('a', Type::string()))
                ) extends EntitySet {
                    public function generate(): array
                    {
                        return array_slice([
                            (new Entity($this))
                                ->addPrimitive('a', $this->getType()->getProperty('a'))
                        ], $this->skip, $this->top);
                    }
                });
        } catch (Exception $e) {
        }
    }
}
