<?php

namespace Flat3\OData\Operation;

use Flat3\OData\Exception\Internal\LexerException;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\Interfaces\ArgumentInterface;
use Flat3\OData\PrimitiveType;

class PrimitiveTypeArgument extends Argument
{
    public function generate($source = null): ArgumentInterface
    {
        $lexer = new Lexer($source);

        $type = $this->getType();

        if (null === $type->get()) {
            if (!$this->isNullable()) {
                throw new BadRequestException(
                    'non_null_argument_missing',
                    sprintf('A non-null argument (%s) is missing', $this->getName())
                );
            }

            return $type;
        }

        try {
            return $lexer->type($type);
        } catch (LexerException $e) {
            throw new BadRequestException(
                'invalid_argument_type',
                sprintf(
                    'The provided argument %s was not of type %s',
                    $this->getName(),
                    $type->getName()
                )
            );
        }
    }

    public function isNullable(): bool
    {
        return $this->parameter->allowsNull();
    }

    public function getType(): PrimitiveType
    {
        /** @var PrimitiveType $reflectedType */
        $reflectedType = $this->parameter->getType()->getName();

        /** @var PrimitiveType $type */
        return new $reflectedType(null, $this->isNullable());
    }
}
