<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Exception\Protocol\ConfigurationException;
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
     */
    private $name;

    public function __construct(string $name)
    {
        if (!Lexer::patternCheck(Lexer::identifier, $name)) {
            throw new ConfigurationException('invalid_name', 'The provided name was invalid: '.$name);
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
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
