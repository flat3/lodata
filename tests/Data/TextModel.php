<?php

namespace Flat3\OData\Tests\Data;

use Flat3\OData\DeclaredProperty;
use Flat3\OData\EntitySet;
use Flat3\OData\Model;
use Flat3\OData\PrimitiveType;

trait TextModel
{
    public function withTextModel()
    {
        Model::add(
            new class(
                'texts',
                Model::entitytype('text')
                    ->addProperty(DeclaredProperty::factory('a', PrimitiveType::string()))
            ) extends EntitySet {
                public function generate(): array
                {
                    return [
                        $this->makeEntity()
                            ->setPrimitive('a', 'a')
                    ];
                }
            });

        Model::fn('textf1')
            ->setCallback(function (EntitySet $texts): EntitySet {
                return $texts;
            })
            ->setType(Model::getType('text'));
    }
}