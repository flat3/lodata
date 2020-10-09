<?php

namespace Flat3\OData\Annotation\Capabilities\V1;

use Flat3\OData\Annotation;
use Flat3\OData\Transaction\Metadata;
use Flat3\OData\Transaction\Metadata\Full;
use Flat3\OData\Transaction\Metadata\Minimal;
use Flat3\OData\Transaction\Metadata\None;
use Flat3\OData\Transaction\ParameterList;
use Flat3\OData\Type\Boolean;
use Flat3\OData\Type\Collection;
use Flat3\OData\Type\String_;

class SupportedFormats extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.SupportedFormats';

    public function __construct()
    {
        $this->type = new Collection();

        /** @var Metadata $attribute */
        foreach ([Full::class, Minimal::class, None::class] as $attribute) {
            $this->type->set(new String_(
                'application/json;' . (new ParameterList())
                    ->addParameter('odata.metadata', $attribute::name)
                    ->addParameter('IEEE754Compatible', Boolean::URL_TRUE)
                    ->addParameter('odata.streaming', Boolean::URL_TRUE)
            ));
        }
    }
}