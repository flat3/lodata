<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation;

use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\TypeInterface;
use Flat3\Lodata\Traits\HasComplexType;

/**
 * Class Record
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530444
 * @package Flat3\Lodata\Annotation
 */
class Record extends ObjectArray implements TypeInterface
{
    use HasComplexType;
}