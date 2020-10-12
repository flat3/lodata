<?php

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Expression\Lexer;

final class Name
{
    /** @var string $name */
    private $name;

    public function __construct(string $name)
    {
        if (!Lexer::patternCheck(Lexer::ODATA_IDENTIFIER, $name)) {
            throw new InternalServerErrorException('invalid_name', 'The provided name was invalid: '.$name);
        }

        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
