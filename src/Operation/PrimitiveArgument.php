<?php

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Interfaces\Operation\ArgumentInterface;
use Flat3\Lodata\PrimitiveType;

class PrimitiveArgument extends Argument
{
    /**
     * Generate a primitive argument
     * @param  null  $source
     * @return ArgumentInterface
     */
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

    /**
     * Whether this primitive can represent a null value
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->parameter->allowsNull();
    }

    /**
     * Get the type of this primitive
     * @return PrimitiveType
     */
    public function getType()
    {
        return new PrimitiveType($this->parameter->getType()->getName());
    }
}
