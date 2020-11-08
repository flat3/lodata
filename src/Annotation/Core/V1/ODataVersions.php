<?php

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Transaction\Version;
use Flat3\Lodata\Type\String_;

/**
 * OData Versions
 * @package Flat3\Lodata\Annotation\Core\V1
 */
class ODataVersions extends Annotation
{
    protected $name = 'Org.OData.Core.V1.ODataVersions';

    public function __construct()
    {
        $this->value = new String_(Version::version);
    }
}