<?php

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Transaction\MediaType;
use Flat3\Lodata\Type\Collection;
use Flat3\Lodata\Type\String_;

/**
 * Supported Metadata Formats
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class SupportedMetadataFormats extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.SupportedMetadataFormats';

    public function __construct()
    {
        $this->value = new Collection();
        $this->value->add(new String_(MediaType::json));
        $this->value->add(new String_(MediaType::xml));
    }
}