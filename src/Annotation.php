<?php

namespace Flat3\OData;

use Flat3\OData\Traits\HasType;
use Flat3\OData\Type\Boolean;
use Flat3\OData\Type\Collection;
use Flat3\OData\Type\Enum;
use Flat3\OData\Type\String_;
use SimpleXMLElement;

class Annotation
{
    use HasType;

    protected $name;

    public function append(SimpleXMLElement $schema): self
    {
        $annotation = $schema->addChild('Annotation');
        $annotation->addAttribute('Term', $this->name);

        switch (true) {
            case $this->type instanceof Boolean:
                $annotation->addAttribute('Bool', $this->type->toUrl());
                break;

            case $this->type instanceof String_:
                $annotation->addAttribute('String', $this->type->get());
                break;

            case $this->type instanceof Enum:
                $annotation->addAttribute('EnumMember', $this->type->toUrl());
                break;

            case $this->type instanceof Collection:
                $collection = $annotation->addChild('Collection');
                foreach ($this->type->get() as $member) {
                    switch (true) {
                        case $member instanceof String_:
                            $collection->addChild('String', $member->get());
                            break;
                    }
                }
                break;
        }

        return $this;
    }

    public function __toString()
    {
        return $this->name;
    }
}
