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
    /** @var Transaction $transaction */
    protected $transaction;

    public function response(): Response
    {
        $transaction = $this->transaction;
        $transaction->ensureMethod(Request::METHOD_GET);
        $transaction->configureJsonResponse();

        return $transaction->getResponse()->setCallback(function () {
            $this->emit();
        });
    }

    public function emit(): void
    {
        $transaction = $this->transaction;

        $transaction->outputJsonObjectStart();

        $metadata = [
            'context' => Transaction::getContextUrl(),
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

    public function setTransaction(Transaction $transaction):self
    {
        $this->transaction = $transaction;
        return $this;
    }
}
