<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Flat3\Lodata\Annotation\Record;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Traits\HasIdentifier;
use Flat3\Lodata\Type\Boolean;
use Flat3\Lodata\Type\Byte;
use Flat3\Lodata\Type\Collection;
use Flat3\Lodata\Type\Enum;
use Flat3\Lodata\Type\String_;
use SimpleXMLElement;

/**
 * Annotation
 * @package Flat3\Lodata
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530341
 */
class Annotation implements IdentifierInterface
{
    use HasIdentifier;

    const identifier = 'Edm.AnnotationPath';

    /**
     * @var Primitive $value Annotation value
     */
    protected $value;

    /**
     * Append the annotation to the provided schema element
     * @param  object  $schema
     * @return $this
     */
    public function appendJson(object $schema): self
    {
        $schema->{'@'.$this->identifier} = $this->appendJsonValue($this->value);

        return $this;
    }

    /**
     * Append a JSON annotation value
     * @param $value
     * @return mixed
     */
    public function appendJsonValue($value)
    {
        switch (true) {
            case $value instanceof Enum:
                return $value->getIdentifier().'/'.$value->toJson();

            case $value instanceof Record:
                $record = (object) [];

                /** @var PropertyValue $propertyValue */
                foreach ($value as $propertyValue) {
                    $record->{$propertyValue->getProperty()->getName()} = $this->appendJsonValue($propertyValue->getPrimitive());
                }

                return $record;

            case $value instanceof Collection:
                $collection = [];

                foreach ($value->get() as $member) {
                    $collection[] = $this->appendJsonValue($member);
                }

                return $collection;

            default:
                return $value->toJson();
        }
    }

    /**
     * Append the annotation to the provided schema element
     * @param  SimpleXMLElement  $schema
     * @return $this
     */
    public function appendXml(SimpleXMLElement $schema): self
    {
        $annotationElement = $schema->addChild('Annotation');
        $annotationElement->addAttribute('Term', $this->identifier->getQualifiedName());
        $this->appendXmlValue($annotationElement, $this->value);

        return $this;
    }

    /**
     * Append the value to the annotation element
     * @param  SimpleXMLElement  $element
     * @param $value
     */
    protected function appendXmlValue(SimpleXMLElement $element, $value)
    {
        switch (true) {
            case $value instanceof Boolean:
                $element->addAttribute('Bool', $value->toUrl());
                break;

            case $value instanceof Byte:
                $element->addAttribute('Int', $value->toUrl());
                break;

            case $value instanceof String_:
                $element->addAttribute('String', $value->get());
                break;

            case $value instanceof Enum:
                $element->addAttribute('EnumMember', $value->getIdentifier().'/'.$value->toJson());
                break;

            case $value instanceof Record:
                $this->appendXmlRecord($element, $value);
                break;

            case $value instanceof Collection:
                $collectionElement = $element->addChild('Collection');

                foreach ($value->get() as $member) {
                    switch (true) {
                        case $member instanceof String_:
                            $collectionElement->addChild('String', $member->get());
                            break;

                        case $member instanceof Record:
                            $this->appendXmlRecord($collectionElement, $member);
                            break;
                    }
                }
                break;
        }
    }

    /**
     * Append the record to the element
     * @param  SimpleXMLElement  $element
     * @param  Record  $record
     */
    protected function appendXmlRecord(SimpleXMLElement $element, Record $record)
    {
        $recordElement = $element->addChild('Record');

        /** @var PropertyValue $propertyValue */
        foreach ($record as $propertyValue) {
            $propertyValueElement = $recordElement->addChild('PropertyValue');
            $propertyValueElement->addAttribute('Property', $propertyValue->getProperty()->getName());
            $this->appendXmlValue($propertyValueElement, $propertyValue->getValue());
        }
    }

    /**
     * Get the value of the annotation
     * @return Primitive
     */
    public function getValue(): Primitive
    {
        return $this->value;
    }

    /**
     * Set the value of the annotation
     * @param  Primitive  $value
     * @return $this
     */
    public function setValue(Primitive $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get the value of this annotation suitable for JSON
     * @return mixed
     */
    public function toJson()
    {
        return $this->value->toJson();
    }

    /**
     * Get the model annotation represented by this class
     * @return Annotation|null
     */
    public static function getModelAnnotation(): ?Annotation
    {
        return Lodata::getAnnotations()->sliceByClass(static::class)->first();
    }
}
