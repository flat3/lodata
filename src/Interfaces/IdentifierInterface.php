<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces;

/**
 * Identifier Interface
 * @package Flat3\Lodata\Interfaces
 */
interface IdentifierInterface extends NameInterface
{
    /**
     * Get the fully qualified name of this nominal item
     * @return string Qualified name
     */
    public function getIdentifier(): string;

    /**
     * Get the namespace of this nominal item
     * @return string Namespace
     */
    public function getNamespace(): string;

    /**
     * Get the name of this item, qualified if required based on the provided namespace
     * @param  string  $namespace  Namespace
     * @return string Name
     */
    public function getResolvedName(string $namespace): string;
}