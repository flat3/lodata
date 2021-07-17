<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction\MetadataType;

use Flat3\Lodata\Transaction\MetadataType;

/**
 * None
 * @package Flat3\Lodata\Transaction\Metadata
 */
final class None extends MetadataType
{
    public const name = 'none';
    protected $requiredProperties = ['nextLink', 'count'];
}
