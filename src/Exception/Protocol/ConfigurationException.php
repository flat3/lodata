<?php

declare(strict_types=1);

namespace Flat3\Lodata\Exception\Protocol;

/**
 * Configuration Error Exception
 * @package Flat3\Lodata\Exception\Protocol
 */
class ConfigurationException extends InternalServerErrorException
{
    protected $odataCode = 'configuration_error';
    protected $message = 'Internal configuration error';
}
