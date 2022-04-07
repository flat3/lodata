<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Transaction\Version;
use Flat3\Lodata\Type\String_;

/**
 * OData Versions
 * @package Flat3\Lodata\Annotation\Core\V1
 */
class ODataVersions extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Core.V1.ODataVersions');
        $this->value = new String_(Version::v4_01);
    }
}