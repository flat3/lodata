<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\ObjectArray;

/**
 * Parameter List
 * @package Flat3\Lodata\Transaction
 */
class ParameterList
{
    /** @var ObjectArray $parameters */
    private $parameters;

    public function __construct()
    {
        $this->parameters = new ObjectArray();
    }

    /**
     * Parse a string into separate parameters
     * @param  string|null  $text
     */
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
            throw new InternalServerErrorException(
                'invalid_parameterlist',
                'An invalid parameter list was provided',
                $e
            );
        }
    }

    /**
     * Get a parameter by key
     * @param  string  $key
     * @return Parameter|null
     */
    public function getParameter(string $key): ?Parameter
    {
        return $this->parameters[$key];
    }
}
