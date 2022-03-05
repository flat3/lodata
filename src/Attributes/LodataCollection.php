<?php

declare(strict_types=1);

namespace Flat3\Lodata\Attributes;

use Attribute;
use Flat3\Lodata\EnumerationType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Type;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class LodataCollection extends LodataProperty
{
    /** @var string|Type $underlyingType */
    protected ?string $underlyingType = null;

    public function __construct(string $name, ?string $source = null, ?string $underlyingType = null)
    {
        parent::__construct($name, $source);
        $this->underlyingType = $underlyingType;
    }

    public function getType(): Type
    {
        $underlyingType = $this->getUnderlyingType();

        $type = Type::collection();

        if (!$underlyingType) {
            return $type;
        }

        if (is_a($underlyingType, Primitive::class, true)) {
            $type->setUnderlyingType(new PrimitiveType($underlyingType));
        } else {
            if (EnumerationType::isEnum($underlyingType)) {
                $type->setUnderlyingType(EnumerationType::discover($underlyingType));
            } else {
                $type->setUnderlyingType(Lodata::getTypeDefinition($underlyingType));
            }
        }

        return $type;
    }

    public function hasUnderlyingType(): bool
    {
        return null !== $this->underlyingType;
    }

    public function getUnderlyingType(): ?string
    {
        return $this->underlyingType;
    }
}