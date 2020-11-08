<?php

namespace Flat3\Lodata\Controller;

use Flat3\Lodata\ServiceProvider;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class PBIDS
 * @link https://docs.microsoft.com/en-us/power-bi/connect-data/desktop-data-sources#using-pbids-files-to-get-data
 * @package Flat3\Lodata\Controller
 */
class PBIDS extends Controller
{
    /**
     * Generate a PowerBI data source discovery file
     * @return Response Client response
     */
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
                            'url' => ServiceProvider::endpoint(),
                        ],
                    ],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES));

        return $response;
    }
}
