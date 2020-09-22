<?php

namespace Flat3\OData\Controller;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Flat3\OData\Exception\NotFoundException;
use Flat3\OData\Exception\PathNotHandledException;
use Flat3\OData\Transaction;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        throw new NotFoundException();
    }
}
