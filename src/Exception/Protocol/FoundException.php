<?php

declare(strict_types=1);

namespace Flat3\Lodata\Exception\Protocol;

use Illuminate\Http\Response;

/**
 * Found Exception
 * @package Flat3\Lodata\Exception\Protocol
 */
class FoundException extends ProtocolException
{
    protected $httpCode = Response::HTTP_FOUND;
    protected $odataCode = 'found';
    protected $message = 'Found';
    protected $suppressContent = true;

    public function __construct(string $location)
    {
        parent::__construct();

        $this->headers['location'] = $location;
    }
}
