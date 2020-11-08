<?php

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Expression\Lexer;

/**
 * Parameter
 * @package Flat3\Lodata\Transaction
 */
class Parameter
{
    private $value = null;
    private $parameters = [];

    /**
     * Parse parameter out of the provided string
     * @param $text
     */
    public function parse($text)
    {
        $lexer = new Lexer($text);

        try {
            while (!$lexer->finished()) {
                $key = $lexer->expression('[^;=]+');
                $value = null;

                if ($lexer->maybeChar('=')) {
                    $value = $lexer->maybeDoubleQuotedString();

                    if (!$value) {
                        $value = $lexer->expression('[^;]+');
                    }
                }

                $this->addParameter($key, $value);
                $lexer->maybeWhitespace();
                $lexer->maybeChar(';');
            }
        } catch (LexerException $e) {
            throw new InternalServerErrorException('invalid_parameter', 'An invalid parameter was provided');
        }
    }

    /**
     * Set parameter value
     * @param $value
     * @return $this
     */
    public function setValue($value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Get parameter value
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Add parameter
     * @param $key
     * @param $value
     * @return $this
     */
    public function addParameter($key, $value): self
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * Drop parameter
     * @param  string  $key
     * @return $this
     */
    public function dropParameter(string $key): self
    {
        unset($this->parameters[$key]);
        return $this;
    }

    /**
     * Get parameter
     * @param  string  $key
     * @return string|null
     */
    public function getParameter(string $key): ?string
    {
        return $this->parameters[$key] ?? null;
    }

    /**
     * Get all parameters
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return string
     * @internal
     */
    public function __toString()
    {
        return http_build_query($this->parameters, '', ';');
    }
}
