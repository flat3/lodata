<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\BatchSupportType;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Transaction\MediaType;
use Flat3\Lodata\Type\Boolean;
use Flat3\Lodata\Type\Collection;

/**
 * Batch support
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class BatchSupport extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Capabilities.V1.BatchSupport');
        $type = new BatchSupportType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(BatchSupportType::supported))
            ->setValue(new Boolean(true));

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(BatchSupportType::etagReferencesSupported))
            ->setValue(new Boolean(true));

        $supportedFormats = new Collection([MediaType::json, MediaType::multipartMixed]);
        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(BatchSupportType::supportedFormats))
            ->setValue($supportedFormats);

        $this->value = $value;
    }
}