<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction\MetadataType;

use Flat3\Lodata\Transaction\MetadataType;

/**
 * Minimal
 * @package Flat3\Lodata\Transaction\Metadata
 */
final class Minimal extends MetadataType
{
    public const name = 'minimal';
    protected $requiredProperties = ['nextLink', 'count', 'context', 'etag', 'deltaLink'];
}
