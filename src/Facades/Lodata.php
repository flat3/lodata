<?php

namespace Flat3\Lodata\Facades;

use Flat3\Lodata\EntityType;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Illuminate\Support\Facades\Facade;

/**
 * Class Lodata
 * @method static ObjectArray getEntityTypes()
 * @method static ObjectArray getResources()
 * @method static ObjectArray getServices()
 * @method static ObjectArray getAnnotationReferences()
 * @method static ObjectArray getAnnotations()
 * @method static ResourceInterface getResource($name)
 * @method static EntityType getEntityType($name)
 * @method static IdentifierInterface add(IdentifierInterface $item)
 * @method static string getNamespace()
 * @method static void discovery()
 * @package Flat3\Lodata\Facades
 */
class Lodata extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'lodata.model';
    }
}