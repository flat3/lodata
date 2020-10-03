<?php

namespace Flat3\OData\Exception\Internal;

use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Expression\Lexer;

final class ParserException extends BadRequestException
{
    public function __construct(string $message, Lexer $lexer = null)
    {
        if ($lexer) {
            $message .= ' at: '.$lexer->errorContext();
        }

        parent::__construct('expression_parser_error', $message);
    }
}
