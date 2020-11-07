<?php

namespace Flat3\Lodata\Controller;

use Flat3\Lodata\Helper\Constants;
use Illuminate\Routing\Controller;

/**
 * Class OData
 * @package Flat3\Lodata\Controller
 */
class OData extends Controller
{
    /**
     * Handle an OData request
     * @param  Request  $request The request
     * @param  Transaction  $transaction Injected transaction
     * @param  Async  $job Injected job
     * @return Response Client response
     */
    public function handle(Request $request, Transaction $transaction, Async $job)
    {
        $transaction->initialize($request);

        if ($transaction->hasPreference(Constants::RESPOND_ASYNC)) {
            $job->setTransaction($transaction);
            $job->dispatch();
        }

        return $transaction->execute()->response($transaction);
    }

    /**
     * PHP 8
     * @param  string  $method
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     * @internal
     */
    public function callAction($method, $parameters)
    {
        return parent::callAction($method, array_values($parameters));
    }
}
