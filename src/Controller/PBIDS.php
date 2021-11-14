<?php

declare(strict_types=1);

namespace Flat3\Lodata\Controller;

use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\ServiceProvider;
use Flat3\Lodata\Transaction\MediaType;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * PBIDS
 * @link https://docs.microsoft.com/en-us/power-bi/connect-data/desktop-data-sources#using-pbids-files-to-get-data
 * @package Flat3\Lodata\Controller
 */
class PBIDS extends Controller
{
    /**
     * Generate a PowerBI data source discovery file
     * @return Response Client response
     */
    public function get(): Response
    {
        $response = new Response();
        $response->header(Constants::contentType, MediaType::json);

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
