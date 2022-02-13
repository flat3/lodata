<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper\Symfony;

use Flat3\Lodata\Controller\Response;

/**
 * Response (Symfony 6)
 * @package Flat3\Lodata\Helper\Symfony
 */
class Response6 extends Response
{
    public function sendContent(): static
    {
        return $this->_sendContent();
    }
}