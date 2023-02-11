<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Exception\Protocol\ConfigurationException;
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
     */
    private $name;

    /**
     * Namespace
     * @var string $namespace
     */
    private $namespace;

    public function __construct(string $identifier)
    {
        if (!Str::contains($identifier, '.')) {
            $identifier = config('lodata.namespace').'.'.$identifier;
        }

        if (!Lexer::patternCheck(Lexer::qualifiedIdentifier, $identifier)) {
            throw new ConfigurationException('invalid_name', 'The provided name was invalid: '.$identifier);
        }

        $this->name = Str::afterLast($identifier, '.');
        $this->namespace = Str::beforeLast($identifier, '.');
    }

    /**
     * Factory method to create an identifier
     * @param  string  $identifier  Identifier
     * @return Identifier
     */
    public static function from(string $identifier): Identifier
    {
        return new self($identifier);
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
     * Set the identifier name
     * @param  string  $name  Name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
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
     * Set the identifier namespace
     * @param  string  $namespace  Namespace
     * @return $this
     */
    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * Get the fully qualified name for this identifier
     * @return string
     */
    public function getQualifiedName(): string
    {
        return $this->namespace.'.'.$this->name;
    }

    /**
     * Get the resolved name of this item based on the provided namespace
     * @param  string  $namespace  Namespace
     * @return string
     */
    public function getResolvedName(string $namespace): string
    {
        if ($this->getNamespace() === $namespace) {
            return $this->getName();
        }

        return $this->getQualifiedName();
    }

    /**
     * Return whether this identifier has the same namespace as the provided identifier
     * @param  string  $identifier
     * @return bool
     */
    public function matchesNamespace(string $identifier): bool
    {
        return $this->namespace === (new Identifier($identifier))->getNamespace();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getQualifiedName();
    }
}
