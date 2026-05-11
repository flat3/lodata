<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\JsonInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\ResponseInterface;
use Flat3\Lodata\Interfaces\SerializeInterface;
use Flat3\Lodata\Traits\HasIdentifier;
use Stringable;

/**
 * Primitive
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530338
 * @package Flat3\Lodata
 */
abstract class Primitive implements ResourceInterface, ContextInterface, IdentifierInterface, ResponseInterface, JsonInterface, PipeInterface, SerializeInterface
{
    use HasIdentifier;

    /**
     * The OData name of this primitive type
     * @type string identifier
     */
    const identifier = 'Edm.PrimitiveType';

    /**
     * The underlying type class of this type
     * @type ?PrimitiveType underlyingType
     */
    const underlyingType = null;

    /**
     * Internal representation of the value
     * @var ?mixed $value
     */
    protected $value;

    public function __construct($value = null)
    {
        $this->set($value);
    }

    /**
     * Set the value of this primitive
     * @param  mixed  $value  Value
     * @return Primitive
     */
    abstract public function set($value);

    /**
     * Test the provided value is formatted correctly for this type
     * @param $value
     * @return bool
     */
    public function allows($value): bool
    {
        return true;
    }

    /**
     * Get the internal representation of the value
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * Get the value in a format suitable for JSON encoding in IEEE754 mode
     * @return string
     */
    public function toJsonIeee754()
    {
        return $this->toJson();
    }

    /**
     * Get the value in a format suitable for an OData URL
     * @return string
     */
    abstract public function toUrl(): string;

    /**
     * Get the value in a format suitable for JSON encoding
     * @return mixed
     */
    abstract public function toJson();

    /**
     * Get the resource URL of this primitive type
     * @param  Transaction  $transaction  Related transaction
     * @return string Resource URL
     */
    public function getResourceUrl(Transaction $transaction): string
    {
        return $transaction->getResourceUrl().$this->getName().'()';
    }

    /**
     * Get the fully qualified identifier of this primitive type
     * @return string Identifier
     */
    public function getIdentifier(): Identifier
    {
        return new Identifier($this::identifier);
    }

    /**
     * Get the context URL of this primitive type
     * @param  Transaction  $transaction  Related transaction
     * @return string Context URL
     */
    public function getContextUrl(Transaction $transaction): string
    {
        return $transaction->getContextUrl().'#'.$this->getIdentifier();
    }

    public function emitJson(Transaction $transaction): void
    {
        $value = $transaction->getIeee754Compatible()->isTrue() ? $this->toJsonIeee754() : $this->toJson();
        $transaction->sendJson($value);
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        if (null === $this->get()) {
            throw new NoContentException('null_value');
        }

        $context = $context ?: $this;

        $metadata = $transaction->createMetadataContainer();

        $metadata['context'] = $context->getContextUrl($transaction);

        return $transaction->getResponse()->setResourceCallback($this, function () use ($transaction, $metadata) {
            $transaction->outputJsonObjectStart();

            if ($metadata->hasProperties()) {
                $transaction->outputJsonKV($metadata->getProperties());
                $transaction->outputJsonSeparator();
            }

            $transaction->outputJsonKey('value');
            $this->emitJson($transaction);

            $transaction->outputJsonObjectEnd();
        });
    }

    /**
     * Return a primitive using the supplied Lexer state
     * @param  Lexer  $lexer  Lexer
     * @return Primitive
     */
    public static function fromLexer(Lexer $lexer): Primitive
    {
        throw new NotImplementedException();
    }

    /**
     * Return whether the provided value is equal to this one
     * @param  Primitive  $value
     * @return bool
     */
    public function equals(Primitive $value): bool
    {
        return $value instanceof $this && $value->get() === $this->get();
    }

    /**
     * Return whether the provided value is a representation of this one
     * @param  mixed  $value
     * @return bool
     */
    public function matches($value): bool
    {
        if ($value instanceof self) {
            $value = $value->get();
        }

        if (is_scalar($value) || is_null($value) || $value instanceof Stringable) {
            return (string) $this->get() === (string) $value;
        }

        return false;
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        return $argument;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }

    /**
     * The OpenAPI schema definition of this primitive
     * @return array Schema
     */
    abstract public function getOpenAPISchema(?Property $property = null): array;
}
