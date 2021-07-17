<?php

declare(strict_types=1);

namespace Flat3\Lodata\Exception\Protocol;

use Flat3\Lodata\Expression\Lexer;
use Illuminate\Http\Response;

/**
 * Bad Request Exception
 * @package Flat3\Lodata\Exception\Protocol
 */
class BadRequestException extends ProtocolException
{
    protected $httpCode = Response::HTTP_BAD_REQUEST;
    protected $odataCode = 'bad_request';
    protected $message = 'Bad request';

    public function lexer(Lexer $lexer): self
    {
        $this->addInnerError('lexer_error', $lexer->errorContext());

        return $this;
    }
}
