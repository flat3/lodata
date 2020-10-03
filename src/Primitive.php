<?php

namespace Flat3\OData;

use Flat3\OData\Exception\Internal\LexerException;
use Flat3\OData\Exception\Internal\PathNotHandledException;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\NoContentException;
use Flat3\OData\Exception\Protocol\NotFoundException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\Interfaces\EmitInterface;
use Flat3\OData\Interfaces\PipeInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class Primitive
 * @package Flat3\OData
 */
abstract class Primitive extends Type implements EmitInterface, PipeInterface
{
    public const URL_NULL = 'null';
    public const URL_TRUE = 'true';
    public const URL_FALSE = 'false';

    /** @var bool $nullable Whether the value can be made null */
    protected $nullable = true;

    /** @var ?mixed $value Internal representation of the value */
    protected $value;

    public function __construct($value = null, bool $nullable = true)
    {
        $this->nullable = $nullable;
        $this->toInternal($value);
    }

    /**
     * Convert the provided value to the internal representation
     *
     * @param $value
     */
    abstract public function toInternal($value): void;

    public static function factory($value = null, ?bool $nullable = true): self
    {
        if ($value instanceof Primitive) {
            return $value;
        }

        return new static($value, $nullable);
    }

    /**
     * Get the internal representation of the value
     *
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * Get the value as OData URL encoded
     *
     * @return string
     */
    abstract public function toUrl(): string;

    /**
     * Get the value as suitable for IEEE754 JSON encoding
     *
     * @return string
     */
    public function toJsonIeee754(): ?string
    {
        $value = $this->toJson();

        return null === $value ? null : (string) $value;
    }

    /**
     * Get the value as suitable for JSON encoding
     *
     * @return mixed
     */
    abstract public function toJson();

    /**
     * Return null or an empty value if this type cannot be made null
     *
     * @param $value
     *
     * @return mixed
     */
    public function maybeNull($value)
    {
        if (null === $value) {
            return $this->nullable ? null : $this->getEmpty();
        }

        return $value;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function setNullable(bool $nullable): self
    {
        $this->nullable = $nullable;
        return $this;
    }

    protected function getEmpty()
    {
        return '';
    }

    /** @var Entity $entity */
    private $entity;

    /** @var Property $property */
    private $property;

    public function setProperty(Property $property): self
    {
        $this->property = $property;
        return $this;
    }

    public function setEntity(Entity $entity): self
    {
        $this->entity = $entity;
        return $this;
    }

    public function getValue()
    {
        return $this->get();
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function getType(): Type
    {
        return $this;
    }

    public function getTypeName(): string
    {
        return $this->getName();
    }

    public static function pipe(
        Transaction $transaction,
        string $pathComponent,
        ?PipeInterface $argument
    ): ?PipeInterface {
        $lexer = new Lexer($pathComponent);

        try {
            $property = $lexer->odataIdentifier();
        } catch (LexerException $e) {
            throw new PathNotHandledException();
        }

        if (null === $argument) {
            throw new PathNotHandledException();
        }

        if (!$argument instanceof Entity) {
            throw new BadRequestException('bad_entity', 'Primitive must be passed an entity');
        }

        $property = $argument->getType()->getProperty($property);

        if (!$property) {
            throw new NotFoundException('unknown_property',
                sprintf('The requested property (%s) was not known', $property));
        }

        return $argument->getPrimitive($property);
    }

    public function emit(Transaction $transaction): void
    {
        $transaction->outputRaw($this);
    }

    public function response(Transaction $transaction): StreamedResponse
    {
        if (null === $this->getValue()) {
            throw new NoContentException('null_value');
        }

        $transaction->setContentTypeJson();

        $metadata = [];

        if ($this->entity) {
            $metadata['context'] = $transaction->getPropertyValueContextUrl(
                $this->entity->getEntitySet(),
                $this->entity->getEntityId()->toUrl(),
                $this->property
            );
        } else {
            $metadata['context'] = $transaction->getTypeContextUrl($this);
        }

        $metadata = $transaction->getMetadata()->filter($metadata);

        return $transaction->getResponse()->setCallback(function () use ($transaction, $metadata) {
            $transaction->outputJsonObjectStart();

            if ($metadata) {
                $transaction->outputJsonKV($metadata);
                $transaction->outputJsonSeparator();
            }

            $transaction->outputJsonKey('value');
            $transaction->outputJsonValue($this);

            $transaction->outputJsonObjectEnd();
        });
    }
}
