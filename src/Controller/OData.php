<?php

namespace Flat3\OData\Controller;

use Flat3\OData\Exception\Protocol\AcceptedException;
use Flat3\OData\Helper\Constants;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Routing\Controller;

class OData extends Controller
{
    public function handle(Request $request, Transaction $transaction)
    {
        $transaction->initialize($request);

        $job = new Job($transaction);

        if ($transaction->getPreference(Constants::RESPOND_ASYNC)) {
            /** @var Dispatcher $dispatcher */
            $dispatcher = app(Dispatcher::class);
            $id = $dispatcher->dispatch($job);
            throw new AcceptedException('');
        }

        return $job->handle()->response($transaction);
    }

    public function callAction($method, $parameters)
    {
        return parent::callAction($method, array_values($parameters));
    }
}
