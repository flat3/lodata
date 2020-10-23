<?php

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Interfaces\ArgumentInterface;
use Flat3\Lodata\PrimitiveType;

class PrimitiveTypeArgument extends Argument
{
    public function generate($source = null): ArgumentInterface
    {
        $lexer = new Lexer($source);

        $type = $this->getType();

        if (null === $source) {
            if (!$this->isNullable()) {
                throw new BadRequestException(
                    'non_null_argument_missing',
                    sprintf('A non-null argument (%s) is missing', $this->getName())
                );
            }

            return $type->instance();
        }

        try {
            return $lexer->type($type);
        } catch (LexerException $e) {
            throw new BadRequestException(
                'invalid_argument_type',
                sprintf(
                    'The provided argument %s was not of type %s',
                    $this->getName(),
                    $type->getIdentifier()
                )
            );
        }
    }

    public function isNullable(): bool
    {
        return $this->parameter->allowsNull();
    }

    public function getType()
    {
        return new PrimitiveType($this->parameter->getType()->getName());
    }
}
