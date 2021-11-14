<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Helper\Identifier;

/**
 * Identifier Interface
 * @package Flat3\Lodata\Interfaces
 */
interface IdentifierInterface extends NameInterface
{
    /**
     * Get the fully qualified name of this nominal item
     * @return Identifier Qualified name
     */
    public function getIdentifier(): Identifier;
}