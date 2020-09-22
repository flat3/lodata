<?php

namespace Flat3\OData\Exception\Protocol;

use Flat3\OData\Expression\Lexer;
use Illuminate\Http\Response;

class BadRequestException extends ProtocolException
{
    protected $httpCode = Response::HTTP_BAD_REQUEST;
    protected $odataCode = 'bad_request';
    protected $message = 'Bad request';

    public function lexer(Lexer $lexer): self
    {
        $this->details = $lexer->errorContext();
        return $this;
    }
}
