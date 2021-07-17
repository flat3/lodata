<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Expression\Lexer;

/**
 * Name
 * @package Flat3\Lodata\Helper
 */
final class Name
{
    /**
     * Name
     * @var string $name
     * @internal
     */
    private $name;

    public function __construct(string $name)
    {
        if (!Lexer::patternCheck(Lexer::IDENTIFIER, $name)) {
            throw new InternalServerErrorException('invalid_name', 'The provided name was invalid: '.$name);
        }

        $this->name = $name;
    }

    /**
     * Get the name
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     * @internal
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
