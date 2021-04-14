<?php

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class BatchSupportType extends ComplexType
{
    const Supported = 'Supported';
    const ReferencesInRequestBodiesSupported = 'ReferencesInRequestBodiesSupported';
    const EtagReferencesSupported = 'EtagReferencesSupported';
    const SupportedFormats = 'SupportedFormats';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.BatchSupportType');

        $this->addDeclaredProperty(self::Supported, Type::boolean());
        $this->addDeclaredProperty(self::ReferencesInRequestBodiesSupported, Type::boolean());
        $this->addDeclaredProperty(self::EtagReferencesSupported, Type::boolean());
        $this->addDeclaredProperty(self::SupportedFormats, Type::collection());
    }
}