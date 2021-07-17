<?php

declare(strict_types=1);

namespace Flat3\Lodata\Console;

/**
 * Class FunctionCommand
 * @package Flat3\Lodata\Console
 */
class FunctionCommand extends ClassCreator
{
    protected $name = 'lodata:function';
    protected $description = 'Create a new Function';
    protected $stub = __DIR__.'/_stubs_/function.php.stub';
}