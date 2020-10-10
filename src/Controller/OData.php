<?php

namespace Flat3\OData\Controller;

use Flat3\OData\Helper\Constants;
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

        return $transaction->execute()->response($transaction);
    }

    public function callAction($method, $parameters)
    {
        return parent::callAction($method, array_values($parameters));
    }
}
