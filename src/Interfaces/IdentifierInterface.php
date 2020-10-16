<?php

namespace Flat3\Lodata\Interfaces;

interface IdentifierInterface
{
    public function getIdentifier(): string;

    public function getName(): string;

    public function getNamespace(): string;

    public function getResolvedName(string $namespace): string;
}