<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Interfaces\PipeInterface;

/**
 * Entity Type
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530349
 * @package Flat3\Lodata
 */
class EntityType extends ComplexType implements PipeInterface
{
    /**
     * Primary key property
     * @var DeclaredProperty $key
     * @internal
     */
    protected $key;

    /**
     * Return the defined key of this entity type
     * @return DeclaredProperty|null
     */
    public function getKey(): ?DeclaredProperty
    {
        return $this->key;
    }

    /**
     * Generate a new entity type
     * @param  string|Identifier  $identifier
     * @return EntityType Entity Type
     * @codeCoverageIgnore
     */
    public static function factory($identifier)
    {
        return new self($identifier);
    }

    /**
     * Set the entity type key property
     * @param  DeclaredProperty  $key  Key property
     * @return $this
     */
    public function setKey(DeclaredProperty $key): self
    {
        $this->addProperty($key);

        // Key property is not nullable
        $key->setNullable(false);

        // Key property should be marked keyable
        $key->setAlternativeKey(true);

        $this->key = $key;

        return $this;
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        $entityType = Lodata::getEntityType($currentSegment);

        if (!$entityType) {
            throw new PathNotHandledException();
        }

        if (!$argument instanceof Entity) {
            throw new PathNotHandledException();
        }

        if ($argument->getType()->getIdentifier() !== $entityType->getIdentifier()) {
            throw new NotFoundException('invalid_entity_type', 'The provided type did not match the entity type');
        }

        return $argument;
    }
}
