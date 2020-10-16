<?php

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Expression\Lexer;
use Illuminate\Support\Str;

final class Identifier
{
    /** @var string $name */
    private $name;

    /** @var string $namespace */
    private $namespace;

    public function __construct(string $identifier)
    {
        if (!Str::contains($identifier, '.')) {
            $identifier = config('lodata.namespace').'.'.$identifier;
        }

        if (!Lexer::patternCheck(Lexer::QUALIFIED_IDENTIFIER, $identifier)) {
            throw new InternalServerErrorException('invalid_name', 'The provided name was invalid: '.$identifier);
        }

        $this->name = Laravel::afterLast($identifier, '.');
        $this->namespace = Laravel::beforeLast($identifier, '.');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function __toString(): string
    {
        return $this->namespace.'.'.$this->name;
    }
}
