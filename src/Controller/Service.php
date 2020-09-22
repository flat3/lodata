<?php

namespace Flat3\OData\Controller;

use Flat3\OData\DataModel;
use Flat3\OData\Resource;
use Flat3\OData\Transaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class Service extends Controller
{
    public function get(Request $request, Transaction $transaction)
    {
        $transaction->setRequest($request);
        $response = $transaction->getResponse();
        $transaction->setContentTypeJson();

        $response->setCallback(function () use ($transaction) {
            /** @var DataModel $dataModel */
            $dataModel = app()->make(DataModel::class);

            $transaction->outputJsonObjectStart();

            $metadata = $transaction->getMetadata()->filter(['context' => $transaction->getServiceDocumentContextUrl()]);

            if ($metadata) {
                $transaction->outputJsonKV($metadata);
                $transaction->outputJsonSeparator();
            }

            $transaction->outputJsonKey('value');
            $transaction->outputJsonArrayStart();

            $resourceMap = $dataModel->getResources();
            $resources = [];
            foreach ($resourceMap as $resource) {
                $resources[] = $resource;
            }

            while ($resources) {
                /** @var Resource $resource */
                $resource = array_shift($resources);

                $transaction->outputJsonObjectStart();

                $resourceData = [
                    'name' => (string) $resource->getIdentifier(),
                    'kind' => $resource->getEdmType(),
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
        });

        return $response;
    }
}
