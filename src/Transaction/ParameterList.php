<?php

namespace Flat3\OData\Transaction;

use Flat3\OData\Exception\Internal\LexerException;
use Flat3\OData\Exception\Protocol\InternalServerErrorException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\Helper\ObjectArray;

class ParameterList
{
    private $parameters;

    public function __construct()
    {
        $this->parameters = new ObjectArray();
    }

    public function parse(string $text = null)
    {
        if (!$text) {
            return;
        }

        try {
            foreach (array_map('trim', array_filter(explode(',', $text))) as $text) {
                $lexer = new Lexer($text);

                $parameter = new Parameter();

                $key = $lexer->expression('[^;=]+');
                $this->parameters[$key] = $parameter;

                if ($lexer->maybeChar('=')) {
                    $parameter->setValue($lexer->maybeDoubleQuotedString());

                    if (!$parameter->getValue()) {
                        $parameter->setValue($lexer->expression('[^;]+'));
                    }
                }

                $lexer->maybeWhitespace();
                if ($lexer->maybeChar(';')) {
                    $parameter->parse($lexer->remaining());
                }
            }
        } catch (LexerException $e) {
            throw new InternalServerErrorException('invalid_parameterlist', 'An invalid parameter list was provided');
        }
    }

    public function getParameter($key): ?Parameter
    {
        return $this->parameters[$key];
    }
}
