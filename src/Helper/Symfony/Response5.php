<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper\Symfony;

use Flat3\Lodata\Controller\Response;

/**
 * Response (Symfony 5)
 * @package Flat3\Lodata\Helper\Symfony
 */
class Response5 extends Response
{
    public function sendContent(): Response
    {
        return $this->_sendContent();
    }
}