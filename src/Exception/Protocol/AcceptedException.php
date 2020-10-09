<?php

namespace Flat3\OData\Exception\Protocol;

use Illuminate\Http\Response;

class AcceptedException extends ProtocolException
{
    protected $httpCode = Response::HTTP_ACCEPTED;
    protected $odataCode = 'accepted';
    protected $message = 'Accepted';

    public function __construct(string $location, string $code = null, string $message = null)
    {
        parent::__construct($code, $message);
        $this->header('location', $location);
    }

    public function retryAfter(int $seconds): self
    {
        $this->header('retry-after', $seconds);
        return $this;
    }
}
