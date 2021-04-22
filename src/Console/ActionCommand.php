<?php

namespace Flat3\Lodata\Console;

/**
 * Class ActionCommand
 * @package Flat3\Lodata\Console
 */
class ActionCommand extends ClassCreator
{
    protected $name = 'lodata:action';
    protected $description = 'Create a new Action';
    protected $stub = __DIR__.'/_stubs_/action.php.stub';
}