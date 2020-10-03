<?php

namespace Flat3\OData\Controller;

use Flat3\OData\Count;
use Flat3\OData\EntitySet;
use Flat3\OData\Exception\Internal\ParserException;
use Flat3\OData\Exception\Internal\PathNotHandledException;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\MethodNotAllowedException;
use Flat3\OData\Exception\Protocol\NotFoundException;
use Flat3\OData\Interfaces\EmitInterface;
use Flat3\OData\Interfaces\PipeInterface;
use Flat3\OData\Metadata;
use Flat3\OData\Operation;
use Flat3\OData\Primitive;
use Flat3\OData\Service;
use Flat3\OData\Transaction;
use Flat3\OData\Value;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OData extends Controller
{
    /**
     * @param  Request  $request
     * @param  Transaction  $transaction
     * @return StreamedResponse
     * @throws BindingResolutionException
     */
    public function get(Request $request, Transaction $transaction)
    {
        /** @var PipeInterface[] $handlers */
        $handlers = [
            EntitySet::class,
            Metadata::class,
            Value::class,
            Count::class,
            Operation::class,
            Primitive::class,
        ];

        $transaction->initialize($request);

        $pathComponents = $transaction->getPathComponents();

        /** @var PipeInterface|EmitInterface $result */
        $result = null;

        if (!$pathComponents) {
            $service = new Service();
            return $service->response($transaction);
        }

        while ($pathComponents) {
            $pathComponent = array_shift($pathComponents);

            foreach ($handlers as $handler) {
                try {
                    $result = $handler::pipe($transaction, $pathComponent, $result);
                    continue 2;
                } catch (PathNotHandledException $e) {
                    continue;
                }
            }

            throw new NotFoundException('no_handler', 'No route handler was able to process this request');
        }

        if (null === $result) {
            throw NotFoundException::factory('resource_not_found', 'Resource not found');
        }

        if (!$result instanceof EmitInterface) {
            throw new RuntimeException('A handler returned something that could not be emitted');
        }

        return $result->response($transaction);
    }

    public function fallback(Request $request)
    {
        throw MethodNotAllowedException::factory()
            ->message(
                sprintf(
                    'The %s method is not allowed',
                    $request->getMethod()
                )
            )
            ->header('Allow', 'GET');
    }
}
