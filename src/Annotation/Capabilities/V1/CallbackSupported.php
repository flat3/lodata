<?php

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Transaction\Metadata;
use Flat3\Lodata\Transaction\Metadata\Full;
use Flat3\Lodata\Transaction\Metadata\Minimal;
use Flat3\Lodata\Transaction\Metadata\None;
use Flat3\Lodata\Transaction\Parameter;
use Flat3\Lodata\Transaction\ParameterList;
use Flat3\Lodata\Type\Collection;
use Flat3\Lodata\Type\String_;

class CallbackSupported extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.CallbackSupported';

    public function __construct()
    {
        $this->type = new Collection();
        $this->type->seal();
    }
}