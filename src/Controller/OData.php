<?php

namespace Flat3\Lodata\Controller;

use Flat3\Lodata\Helper\Constants;
use Illuminate\Routing\Controller;

class OData extends Controller
{
    public function handle(Request $request, Transaction $transaction, Async $job)
    {
        $transaction->initialize($request);

        if ($transaction->hasPreference(Constants::RESPOND_ASYNC)) {
            $job->setTransaction($transaction);
            $job->dispatch();
        }

        return $transaction->execute()->response();
    }

    public function callAction($method, $parameters)
    {
        return parent::callAction($method, array_values($parameters));
    }
}
