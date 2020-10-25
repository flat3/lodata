<?php

namespace Flat3\Lodata\Interfaces;

interface IdentifierInterface extends NameInterface
{
    public function getIdentifier(): string;

    public function getNamespace(): string;

    public function getResolvedName(string $namespace): string;
}