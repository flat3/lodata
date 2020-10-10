<?php

namespace Flat3\OData\Controller;

use Flat3\OData\Exception\Protocol\NotFoundException;
use Illuminate\Routing\Controller;

class Monitor extends Controller
{
    public function show(string $transactionId)
    {
        $job = new Async($transactionId);

        if ($job->isPending()) {
            $job->accepted();
        }

        if ($job->isDeleted()) {
            throw new NotFoundException();
        }

        $meta = $job->getResultMetadata();

        $response = new Response();
        $response->headers->replace($meta['headers']);
        $response->headers->set('asyncresult', $meta['status']);
        $response->setCallback(function () use ($job) {
            fpassthru($job->getResultStream());
            $job->destroy();
        });

        return $response;
    }

    public function destroy(string $transactionId)
    {
        $job = new Async($transactionId);
        $job->destroy();
    }

    public function callAction($method, $parameters)
    {
        return parent::callAction($method, array_values($parameters));
    }
}
