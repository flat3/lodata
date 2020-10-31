<?php

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\DynamicProperty;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EmitInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Property;
use Flat3\Lodata\Transaction\NavigationRequest;

class PropertyValue implements ContextInterface, PipeInterface, EmitInterface
{
    /** @var Entity $entity */
    protected $entity;

    /** @var Property $property */
    protected $property;

    /** @var mixed $value */
    protected $value;

    public function setProperty(Property $property): self
    {
        $this->property = $property;
        return $this;
    }

    public function getProperty(): Property
    {
        return $this->property;
    }

    public function setEntity(Entity $entity): self
    {
        $this->entity = $entity;
        return $this;
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }

    public function setValue(?EmitInterface $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getValue(): ?EmitInterface
    {
        return $this->value;
    }

    public function getPrimitiveValue(): Primitive
    {
        return $this->value;
    }

    public function getEntitySetValue(): EntitySet
    {
        return $this->value;
    }

    public function getEntityValue(): Entity
    {
        return $this->value;
    }

    public function __toString()
    {
        return (string) $this->property;
    }

    public function shouldEmit(Transaction $transaction): bool
    {
        if ($this->value instanceof Primitive) {
            $omitNulls = $transaction->getPreferenceValue(Constants::OMIT_VALUES) === Constants::NULLS;

            if ($omitNulls && $this->value->get() === null && $this->property->isNullable()) {
                return false;
            }
        }

        if ($this->property instanceof NavigationProperty) {
            return true;
        }

        $select = $transaction->getSelect();

        if ($select->isStar() || !$select->hasValue()) {
            return true;
        }

        $selected = $select->getCommaSeparatedValues();

        if ($selected && !in_array($this->property->getName(), $selected)) {
            return false;
        }

        return true;
    }

    public function getContextUrl(Transaction $transaction): string
    {
        return sprintf(
            '%s(%s)/%s',
            $transaction->getContextUrl().'#'.$this->entity->getEntitySet()->getName(),
            $this->entity->getEntityId()->getValue()->toUrl(),
            $this->property
        );
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        $lexer = new Lexer($currentSegment);

        try {
            $propertyName = $lexer->identifier();
        } catch (LexerException $e) {
            throw new PathNotHandledException();
        }

        if (null === $argument) {
            throw new PathNotHandledException();
        }

        if ($argument instanceof self && $argument->getValue() instanceof Entity) {
            $argument = $argument->getEntityValue();
        }

        if (!$argument instanceof Entity) {
            throw new PathNotHandledException();
        }

        $property = $argument->getType()->getProperty($propertyName);

        if (null === $property) {
            throw new NotFoundException(
                'unknown_property',
                sprintf('The requested property (%s) was not known', $propertyName)
            );
        }

        if ($property instanceof NavigationProperty) {
            $navigationRequest = new NavigationRequest();
            $navigationRequest->setNavigationProperty($property);
            $property->generatePropertyValue($transaction, $navigationRequest, $argument);
        }

        if ($property instanceof DynamicProperty) {
            $property->generatePropertyValue($argument);
        }

        return $argument->getPropertyValues()->get($property);
    }

    public function emit(Transaction $transaction): void
    {
        switch (true) {
            case $this->value instanceof EntitySet:
                $this->value->emit($transaction);
                return;

            case $this->value instanceof Primitive:
                $transaction->outputJsonValue($this);
                return;
        }

        throw new InternalServerErrorException(
            'cannot_emit_property_value',
            'Property value received an object it could not emit'
        );
    }

    public function response(Transaction $transaction): Response
    {
        $value = $this->value;

        if ($value instanceof Primitive && null === $value->get() || $value === null) {
            throw new NoContentException('null_value');
        }

        if ($value instanceof Entity) {
            return $value->response($transaction);
        }

        $metadata = [
            'context' => $this->getContextUrl($transaction),
        ];

        $metadata = $transaction->getMetadata()->filter($metadata);

        return $transaction->getResponse()->setCallback(function () use ($transaction, $metadata) {
            $transaction->outputJsonObjectStart();

            if ($metadata) {
                $transaction->outputJsonKV($metadata);
                $transaction->outputJsonSeparator();
            }

            $transaction->outputJsonKey('value');
            $this->emit($transaction);

            $transaction->outputJsonObjectEnd();
        });
    }
}
