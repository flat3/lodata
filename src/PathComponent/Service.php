<?php

namespace Flat3\Lodata\PathComponent;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\EmitInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;
use Illuminate\Http\Request;

class Service implements EmitInterface
{
    public function response(Transaction $transaction): Response
    {
        $transaction->ensureMethod(Request::METHOD_GET);

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emit($transaction);
        });
    }

    public function emit(Transaction $transaction): void
    {
        $transaction->outputJsonObjectStart();

        $metadata = [
            'context' => $transaction->getContextUrl(),
        ];

        $metadata = $transaction->getMetadata()->filter($metadata);

        if ($metadata) {
            $transaction->outputJsonKV($metadata);
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
