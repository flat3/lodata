<?php

namespace Flat3\OData\Annotation\Core\V1;

use Flat3\OData\Annotation;
use Flat3\OData\Transaction\Version;
use Flat3\OData\Type\String_;

class ODataVersions extends Annotation
{
    protected $name = 'Org.OData.Core.V1.ODataVersions';

    public function __construct()
    {
        $this->type = new String_(Version::version);
        $this->type->seal();
    }
}