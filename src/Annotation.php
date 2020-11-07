<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Type\Boolean;
use Flat3\Lodata\Type\Collection;
use Flat3\Lodata\Type\Enum;
use Flat3\Lodata\Type\String_;
use SimpleXMLElement;

/**
 * Annotation
 * @package Flat3\Lodata
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530341
 */
abstract class Annotation
{
    /**
     * @var string $name Annotation name
     * @internal
     */
    protected $name;

    /**
     * @var Primitive $value Annotation value
     * @internal
     */
    protected $value;

    /**
     * Append the annotation to the provided schema element
     * @param  SimpleXMLElement  $schema
     * @return $this
     */
    public function append(SimpleXMLElement $schema): self
    {
        $annotation = $schema->addChild('Annotation');
        $annotation->addAttribute('Term', $this->name);

        switch (true) {
            case $this->value instanceof Boolean:
                $annotation->addAttribute('Bool', $this->value->toUrl());
                break;

            case $this->value instanceof String_:
                $annotation->addAttribute('String', $this->value->get());
                break;

            case $this->value instanceof Enum:
                $annotation->addAttribute('EnumMember', $this->value->getIdentifier().'/'.$this->value->toUrl());
                break;

            case $this->value instanceof Collection:
                $collection = $annotation->addChild('Collection');
                foreach ($this->value->get() as $member) {
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

    /**
     * @return string
     * @internal
     */
    public function __toString()
    {
        return $this->name;
    }
}
