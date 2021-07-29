<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Expression\Lexer;
use Illuminate\Support\Str;

/**
 * Identifier
 * @package Flat3\Lodata\Helper
 */
final class Identifier
{
    /**
     * Name
     * @var string $name
     * @internal
     */
    private $name;

    /**
     * Namespace
     * @var string $namespace
     * @internal
     */
    private $namespace;

    public function __construct(string $identifier)
    {
        if (!Str::contains($identifier, '.')) {
            $identifier = config('lodata.namespace').'.'.$identifier;
        }

        if (!Lexer::patternCheck(Lexer::qualifiedIdentifier, $identifier)) {
            throw new InternalServerErrorException('invalid_name', 'The provided name was invalid: '.$identifier);
        }

        $this->name = Str::afterLast($identifier, '.');
        $this->namespace = Str::beforeLast($identifier, '.');
    }

    /**
     * Get the identifier name
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the identifier namespace
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return string
     * @internal
     */
    public function __toString(): string
    {
        return $this->namespace.'.'.$this->name;
    }
}
