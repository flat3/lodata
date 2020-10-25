<?php

namespace Flat3\Lodata\Drivers\SQL;

trait SQLParameters
{
    /** @var string[] $parameters */
    protected $parameters = [];

    /**
     * Add a parameter
     *
     * @param $parameter
     */
    protected function addParameter($parameter): void
    {
        $this->parameters[] = $parameter;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    protected function resetParameters(): void
    {
        $this->parameters = [];
    }
}
