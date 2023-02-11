<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Traits\HasIdentifier;
use Flat3\Lodata\Type;
use Flat3\Lodata\Type\Collection;
use Flat3\Lodata\Type\Untyped;

/**
 * Collection Type
 * @package Flat3\Lodata\Helper
 */
class CollectionType extends PrimitiveType implements IdentifierInterface
{
    use HasIdentifier;

    public function __construct(?Type $type = null)
    {
        parent::__construct(Collection::class);
        $this->setUnderlyingType($type ?: (new Untyped));
    }

    public function instance($value = []): Primitive
    {
        return (new Collection)->setCollectionType($this)->set($value);
    }

    public function getIdentifier(): Identifier
    {
        return $this->getUnderlyingType()->getIdentifier();
    }
}
