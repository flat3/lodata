<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces;

/**
 * ETag Interface
 * @package Flat3\Lodata\Interfaces
 */
interface ETagInterface
{
    public function toEtag(): ?string;
}