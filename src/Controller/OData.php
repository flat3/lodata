<?php

namespace Flat3\OData\Controller;

use Flat3\OData\Exception\Internal\ParserException;
use Flat3\OData\Exception\Internal\PathNotHandledException;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\MethodNotAllowedException;
use Flat3\OData\Exception\Protocol\NotFoundException;
use Flat3\OData\Transaction;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
        $handlers = [
            Set::class,
            Singular::class,
            Primitive::class,
            Count::class,
            Raw::class,
            Operation::class,
        ];

        $transaction->setRequest($request);
        $response = $transaction->getResponse();

        foreach ($handlers as $handler) {
            /** @var Handler $instance */
            $instance = app()->make($handler);

            try {
                $instance->setup($transaction);
            } catch (PathNotHandledException $exception) {
                continue;
            }

            try {
                $instance->handle();
            } catch (ParserException $e) {
                throw new BadRequestException('parser_error', $e->getMessage());
            }
            return $response;
        }

        throw new NotFoundException('no_handler', 'No route handler was able to process this request');
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
