<?php

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Transaction\Metadata;
use Flat3\Lodata\Transaction\Metadata\Full;
use Flat3\Lodata\Transaction\Metadata\Minimal;
use Flat3\Lodata\Transaction\Metadata\None;
use Flat3\Lodata\Transaction\Parameter;
use Flat3\Lodata\Type\Collection;
use Flat3\Lodata\Type\String_;

/**
 * Supported Formats
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class SupportedFormats extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.SupportedFormats';

    public function __construct()
    {
        $this->value = new Collection();

        /** @var Metadata $attribute */
        foreach ([Full::class, Minimal::class, None::class] as $attribute) {
            $this->value->set(new String_(
                'application/json;'.(new Parameter())
                    ->addParameter('odata.metadata', $attribute::name)
                    ->addParameter('IEEE754Compatible', Constants::TRUE)
                    ->addParameter('odata.streaming', Constants::TRUE)
            ));
        }
    }
}