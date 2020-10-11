<?php

namespace Flat3\OData\Annotation\Capabilities\V1;

use Flat3\OData\Annotation;
use Flat3\OData\Helper\Constants;
use Flat3\OData\Transaction\Metadata;
use Flat3\OData\Transaction\Metadata\Full;
use Flat3\OData\Transaction\Metadata\Minimal;
use Flat3\OData\Transaction\Metadata\None;
use Flat3\OData\Transaction\Parameter;
use Flat3\OData\Transaction\ParameterList;
use Flat3\OData\Type\Collection;
use Flat3\OData\Type\String_;

class CallbackSupported extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.CallbackSupported';

    public function __construct()
    {
        $this->type = new Collection();
        $this->type->seal();
    }
}