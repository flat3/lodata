<?php

namespace Flat3\Lodata\Controller;

use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Routing\Controller;

/**
 * Monitor
 * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_AsynchronousRequests
 * @package Flat3\Lodata\Controller
 */
class Monitor extends Controller
{
    /**
     * Show the current status or results of the requested job
     * @param  string  $transactionId  Transaction ID
     * @return Response Client response
     */
    public function show(string $transactionId): Response
    {
        $job = new Async();
        $job->setId($transactionId);

        if ($job->isPending()) {
            return $job->accepted()->toResponse();
        }

        if ($job->isDeleted()) {
            return (new NotFoundException())->toResponse();
        }

        $meta = $job->getResultMetadata();

        $response = new Response();
        $response->headers->replace($meta['headers']);
        $response->headers->set('asyncresult', $meta['status']);
        $response->setCallback(function () use ($job) {
            try {
                $resultStream = $job->getResultStream();
                fpassthru($resultStream);
            } catch (FileNotFoundException $e) {
            }

            $job->destroy();
        });

        return $response;
    }

    /**
     * Delete the requested job
     * @param  string  $transactionId  Transaction ID
     */
    public function destroy(string $transactionId)
    {
        $job = new Async();
        $job->setId($transactionId);
        $job->destroy();
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
