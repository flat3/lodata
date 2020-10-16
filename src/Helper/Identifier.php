<?php

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Expression\Lexer;

final class Identifier
{
    /** @var string $identifier */
    private $identifier;

    public function __construct(string $identifier)
    {
        if (!Lexer::patternCheck(Lexer::IDENTIFIER, $identifier)) {
            throw new InternalServerErrorException('invalid_name', 'The provided name was invalid: '.$identifier);
        }

        $this->identifier = $identifier;
    }

    public function __toString(): string
    {
        return $this->identifier;
    }
}
