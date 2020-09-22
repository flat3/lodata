<?php

namespace Flat3\OData\Exception;

final class ParserException extends BadRequestException
{
    public function __construct($message = '', $lexer_context = '')
    {
        if ($lexer_context) {
            $message .= ' at: '.$lexer_context;
        }

        parent::__construct($message);
    }
}
