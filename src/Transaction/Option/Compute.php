<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction\Option;

use Flat3\Lodata\ComputedProperty;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Properties;
use Flat3\Lodata\Transaction\Option;

/**
 * Compute
 * @package Flat3\Lodata\Transaction\Option
 */
class Compute extends Option
{
    public const param = 'compute';

    /**
     * Parse the option into ComputedProperty objects
     * @return ComputedProperty[]|Properties Compute item objects
     */
    public function getProperties(): Properties
    {
        $computedProperties = new Properties();

        if (!$this->hasValue()) {
            return $computedProperties;
        }

        $lexer = new Lexer($this->getValue());

        while (!$lexer->finished()) {
            $commonExpression = $lexer->computeExpression();
            $lexer->literal(' as ');
            $identifier = $lexer->identifier();
            $computedProperty = new ComputedProperty($identifier);
            $computedProperty->setExpression($commonExpression);
            $computedProperties[] = $computedProperty;
            $lexer->maybeExpression(',\s?');
        }

        return $computedProperties;
    }
}
