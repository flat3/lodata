<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\BatchSupportType;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Transaction\MediaType;
use Flat3\Lodata\Type\Boolean;
use Flat3\Lodata\Type\Collection;
use Flat3\Lodata\Type\String_;

/**
 * Batch support
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class BatchSupport extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.BatchSupport';

    public function __construct()
    {
        $type = new BatchSupportType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(BatchSupportType::Supported))
            ->setValue(Boolean::factory(true));

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(BatchSupportType::EtagReferencesSupported))
            ->setValue(Boolean::factory(true));

        $supportedFormats = new Collection();
        $supportedFormats->add(String_::factory(MediaType::json));
        $supportedFormats->add(String_::factory(MediaType::multipartMixed));
        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(BatchSupportType::SupportedFormats))
            ->setValue($supportedFormats);

        $this->value = $value;
    }
}