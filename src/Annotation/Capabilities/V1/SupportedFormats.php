<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Transaction\MediaType;
use Flat3\Lodata\Transaction\MetadataType;
use Flat3\Lodata\Transaction\MetadataType\Full;
use Flat3\Lodata\Transaction\MetadataType\Minimal;
use Flat3\Lodata\Transaction\MetadataType\None;
use Flat3\Lodata\Type\Collection;

/**
 * Supported Formats
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class SupportedFormats extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Capabilities.V1.SupportedFormats');
        $this->value = new Collection();

        /** @var MetadataType $attribute */
        foreach ([Full::class, Minimal::class, None::class] as $attribute) {
            $this->value[] = (string) (new MediaType)->parse(MediaType::json)
                ->setParameter(Constants::metadata, $attribute::name)
                ->setParameter(Constants::ieee754Compatible, Constants::true)
                ->setParameter(Constants::streaming, Constants::true);
        }
    }
}