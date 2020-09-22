<?php

namespace Flat3\OData\Controller;

use Illuminate\Routing\Controller;
use Illuminate\Http\Response;
use Flat3\OData\ServiceProvider;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class PBIDS extends Controller
{
    public function get()
    {
        $response = new Response();
        $response->header('content-type', 'application/json');

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'odata.pbids'
        );
        $response->headers->set('Content-Disposition', $disposition);

        $response->setContent(json_encode([
            'version' => '0.1',
            'connections' => [
                [
                    'details' => [
                        'protocol' => 'odata',
                        'address' => [
                            'url' => ServiceProvider::restEndpoint(),
                        ],
                    ],
                ],
            ],
        ]));

        return $response;
    }
}
