<?php

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\NotAcceptableException;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResponseInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Operation\Argument;
use Flat3\Lodata\Operation\EntityArgument;
use Flat3\Lodata\Operation\EntitySetArgument;
use Flat3\Lodata\Operation\PrimitiveArgument;
use Illuminate\Http\Request;

/**
 * Metadata
 * @package Flat3\Lodata\PathSegment
 */
abstract class Metadata implements PipeInterface, ResponseInterface
{
    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): PipeInterface {
        if ($currentSegment !== '$metadata') {
            throw new PathNotHandledException();
        }

        if ($argument || $nextSegment) {
            throw new BadRequestException('metadata_argument', '$metadata must be the only argument in the path');
        }

        $transaction->assertMethod(Request::METHOD_GET);

        $contentType = $transaction->getAcceptedContentType();

        switch ($contentType->getSubtype()) {
            case 'xml':
            case '*':
                return new Metadata\XML();

            case 'json':
                return new Metadata\JSON();

            default:
                throw new NotAcceptableException(
                    'unknown_metadata_type',
                    'The requested metadata content type was not known'
                );
        }
    }

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
