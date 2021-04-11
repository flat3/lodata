<?php

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Operation\Argument;
use Flat3\Lodata\Operation\EntityArgument;
use Flat3\Lodata\Operation\EntitySetArgument;
use Flat3\Lodata\Operation\PrimitiveArgument;

/**
 * Class Metadata
 * Provides CSDL metadata
 * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part2-url-conventions/odata-v4.01-os-part2-url-conventions.html#sec_AddressingtheModelforaService
 * @package Flat3\Lodata\Transaction
 */
abstract class Metadata
{
    /**
     * Extract operation arguments for metadata
     * Ensure the binding parameter is first, if it exists. Filter out non-odata arguments.
     * @param  Operation  $resource
     * @return ObjectArray|Argument[]
     */
    protected function getOperationArguments(Operation $resource)
    {
        return $resource->getArguments()->sort(function (Argument $a, Argument $b) use ($resource) {
            if ($a->getName() === $resource->getBindingParameterName()) {
                return -1;
            }

            if ($b->getName() === $resource->getBindingParameterName()) {
                return 1;
            }

            return 0;
        })->filter(function ($argument) use ($resource) {
            if ($argument instanceof PrimitiveArgument) {
                return true;
            }

            if (($argument instanceof EntitySetArgument || $argument instanceof EntityArgument) && $resource->getBindingParameterName() === $argument->getName()) {
                return true;
            }

            return false;
        });
    }
}