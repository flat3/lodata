<?php

namespace Flat3\OData\PathComponent;

use Flat3\OData\Controller\Transaction;
use Flat3\OData\Interfaces\EmitInterface;
use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Interfaces\ResourceInterface;
use Flat3\OData\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Service implements EmitInterface
{
    public function response(Transaction $transaction): StreamedResponse
    {
        $transaction->setContentTypeJson();

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emit($transaction);
        });
    }

    public function emit(Transaction $transaction):void
    {
        /** @var Model $model */
        $model = app()->make(Model::class);

        $transaction->outputJsonObjectStart();

        $metadata = $transaction->getMetadata()->filter(['context' => $transaction->getServiceDocumentContextUrl()]);

        if ($metadata) {
            $transaction->outputJsonKV($metadata);
            $transaction->outputJsonSeparator();
        }

        $transaction->outputJsonKey('value');
        $transaction->outputJsonArrayStart();

        $resourceMap = $model->getResources();
        $resources = [];
        foreach ($resourceMap as $resource) {
            $resources[] = $resource;
        }

        while ($resources) {
            /** @var ResourceInterface|IdentifierInterface $resource */
            $resource = array_shift($resources);

            $transaction->outputJsonObjectStart();

            $resourceData = [
                'name' => (string) $resource->getIdentifier(),
                'kind' => $resource->getKind(),
                'url' => (string) $resource->getIdentifier(),
            ];

            if ($resource->getTitle()) {
                $resourceData['title'] = $resource->getTitle();
            }

            $transaction->outputJsonKV($resourceData);

            $transaction->outputJsonObjectEnd();

            if ($resources) {
                $transaction->outputJsonSeparator();
            }
        }

        $transaction->outputJsonArrayEnd();
        $transaction->outputJsonObjectEnd();
    }
}
