<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces;

/**
 * Serialize Interface
 * @package Flat3\Lodata\Interfaces
 */
interface SerializeInterface
{
    /**
     * Get the value as a PHP mixed type
     * @link https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.mixed
     * @return mixed
     */
    public function toMixed();
}