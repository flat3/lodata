<?php

declare(strict_types=1);

namespace Flat3\Lodata\Console;

use Illuminate\Console\GeneratorCommand;

/**
 * Class ClassCreator
 * @package Flat3\Lodata\Console
 */
abstract class ClassCreator extends GeneratorCommand
{
    protected $stub = null;

    protected function getStub(): string
    {
        return $this->stub;
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Lodata';
    }

    public function handle(): bool
    {
        if ($this->isReservedName($this->getNameInput())) {
            $this->error(sprintf('The name "%s" is reserved by PHP.', $this->getNameInput()));

            return false;
        }

        $name = $this->qualifyClass($this->getNameInput());

        if (
            !$this->hasOption('force' || !$this->option('force'))
            && $this->alreadyExists($this->getNameInput())
        ) {
            $this->error("{$name} already exists!");

            return false;
        }

        $path = $this->getPath($name);
        $this->makeDirectory($path);
        $this->files->put($path, $this->sortImports($this->buildClass($name)));
        $this->info("{$name} created successfully at {$path}");

        return true;
    }
}