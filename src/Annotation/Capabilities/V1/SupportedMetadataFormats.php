<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Transaction\MediaType;
use Flat3\Lodata\Type\Collection;

/**
 * Supported Metadata Formats
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class SupportedMetadataFormats extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.SupportedMetadataFormats';

    public function __construct()
    {
        $this->value = new Collection([MediaType::json, MediaType::xml]);
    }
}