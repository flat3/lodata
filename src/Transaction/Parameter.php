<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;

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
     * @param  string  $text
     */
    public function parse(string $text)
    {
        $lexer = new Lexer($text);

        try {
            while (!$lexer->finished()) {
                $key = trim($lexer->expression('[^;=]+'));
                $value = null;

                if ($lexer->maybeChar('=')) {
                    $value = $lexer->maybeDoubleQuotedString();

                    if (!$value) {
                        $value = $lexer->expression('[^;]+');
                    }
                }

                // Parameters renamed in OData 4.01
                switch ($key) {
                    case Constants::odataStreaming:
                        $key = Constants::streaming;
                        break;

                    case Constants::odataMetadata:
                        $key = Constants::metadata;
                        break;
                }

                $this->addParameter($key, $value);
                $lexer->maybeWhitespace();
                $lexer->maybeChar(';');
            }
        } catch (LexerException $e) {
            throw new InternalServerErrorException('invalid_parameter', 'An invalid parameter was provided', $e);
        }
    }

    /**
     * Set parameter value
     * @param ?string  $value
     * @return $this
     */
    public function setValue(?string $value): self
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
     * @param  string  $key
     * @param  string  $value
     * @return $this
     */
    public function addParameter(string $key, string $value): self
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
     */
    public function __toString()
    {
        return http_build_query($this->parameters, '', ';');
    }
}
