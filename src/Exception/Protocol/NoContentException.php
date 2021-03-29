<?php

namespace Flat3\Lodata\Exception\Protocol;

use Illuminate\Http\Response;

/**
 * No Content Exception
 * @package Flat3\Lodata\Exception\Protocol
 */
class NoContentException extends ProtocolException
{
    protected $httpCode = Response::HTTP_NO_CONTENT;
    protected $odataCode = 'no_content';
    protected $message = 'No content';

    /**
     * No Content responses do not return content
     * @param  null  $request
     * @return \Flat3\Lodata\Controller\Response
     */
    public function toResponse($request = null): \Flat3\Lodata\Controller\Response
    {
        $response = parent::toResponse($request);

        $response->setCallback(function () {
            return '';
        });

        return $response;
    }
}
