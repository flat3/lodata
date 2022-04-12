<?php

declare(strict_types=1);

namespace Flat3\Lodata\Attributes;

use Attribute;
use Flat3\Lodata\EnumerationType;
use Flat3\Lodata\Exception\Protocol\ConfigurationException;
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
        $requestedType = $this->getUnderlyingType();

        $type = Type::collection();

        if (!$requestedType) {
            return $type;
        }

        switch (true) {
            case is_a($requestedType, Primitive::class, true):
                $underlyingType = new PrimitiveType($requestedType);
                break;

            case EnumerationType::isEnum($requestedType):
                $underlyingType = EnumerationType::discover($requestedType);
                break;

            default:
                $underlyingType = Lodata::getTypeDefinition($requestedType);
                break;
        }

        if (null === $underlyingType) {
            throw new ConfigurationException(
                'missing_underlying_type',
                sprintf('The specified type %s was missing', $requestedType)
            );
        }

        $type->setUnderlyingType($underlyingType);

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