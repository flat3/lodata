<?php

namespace Flat3\OData\PathComponent;

use Flat3\OData\Controller\Transaction;
use Flat3\OData\Interfaces\EmitInterface;
use Flat3\OData\Interfaces\ServiceInterface;
use Flat3\OData\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Service implements EmitInterface
{
    public function response(Transaction $transaction): StreamedResponse
    {
        $transaction->configureJsonResponse();

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emit($transaction);
        });
    }

    public function emit(Transaction $transaction): void
    {
        $model = Model::get();

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

        $serviceMap = $model->getServices();
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
