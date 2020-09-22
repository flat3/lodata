<?php

namespace Flat3\OData\Controller;

use Flat3\OData\Exception\Internal\PathNotHandledException;
use Flat3\OData\Exception\Protocol\NotFoundException;
use Flat3\OData\Transaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OData extends Controller
{
    public function get(Request $request, Transaction $transaction)
    {
        $handlers = [
            Set::class,
            Singular::class,
            Primitive::class,
            Count::class,
            Raw::class,
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

            $instance->handle();
            return $response;
        }

        throw new NotFoundException('no_handler', 'No route handler was able to process this request');
    }
}
