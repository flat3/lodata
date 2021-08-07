<?php

declare(strict_types=1);

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\JsonInterface;
use Flat3\Lodata\Interfaces\ResponseInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;
use Illuminate\Http\Request;

/**
 * Service
 * @package Flat3\Lodata\PathSegment
 */
class Service implements JsonInterface, ResponseInterface
{
    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $transaction->assertMethod(Request::METHOD_GET);

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emitJson($transaction);
        });
    }

    /**
     * Emit the service document
     * @param  Transaction  $transaction  Transaction
     */
    public function emitJson(Transaction $transaction): void
    {
        $transaction->outputJsonObjectStart();

        $metadata = $transaction->createMetadataContainer();
        $metadata['context'] = $transaction->getContextUrl();

        if ($metadata->hasProperties()) {
            $transaction->outputJsonKV($metadata->getProperties());
            $transaction->outputJsonSeparator();
        }

        $transaction->outputJsonKey('value');
        $transaction->outputJsonArrayStart();

        $serviceMap = Lodata::getServices();
        $services = [];
        foreach ($serviceMap as $service) {
            $services[] = $service;
        }

        while ($services) {
            /** @var ServiceInterface $service */
            $service = array_shift($services);

            $transaction->outputJsonObjectStart();

            $resourceData = [
                'name' => (string) $service->getName(),
                'kind' => $service->getKind(),
                'url' => (string) $service->getName(),
            ];

            if ($service->getTitle()) {
                $resourceData['title'] = $service->getTitle();
            }

            $transaction->outputJsonKV($resourceData);

            $transaction->outputJsonObjectEnd();

            if ($services) {
                $transaction->outputJsonSeparator();
            }
        }

        $transaction->outputJsonArrayEnd();
        $transaction->outputJsonObjectEnd();
    }
}
