<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class BatchSupportType extends ComplexType
{
    const supported = 'Supported';
    const referencesInRequestBodiesSupported = 'ReferencesInRequestBodiesSupported';
    const etagReferencesSupported = 'EtagReferencesSupported';
    const supportedFormats = 'SupportedFormats';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.BatchSupportType');

        $this->addDeclaredProperty(self::supported, Type::boolean());
        $this->addDeclaredProperty(self::referencesInRequestBodiesSupported, Type::boolean());
        $this->addDeclaredProperty(self::etagReferencesSupported, Type::boolean());
        $this->addDeclaredProperty(self::supportedFormats, Type::collection());
    }
}