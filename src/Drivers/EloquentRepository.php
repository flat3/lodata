<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\RepositoryInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Operation\Argument;
use Flat3\Lodata\Operation\EntityArgument;

class EloquentRepository extends Operation
{
    protected $kind = Operation::function;

    public function resolveParameters(): array
    {
        if (!$this->getBindingParameterName()) {
            throw new InternalServerErrorException(
                'missing_binding_parameter',
                'A binding parameter name must be provided for this operation'
            );
        }

        return parent::resolveParameters();
    }

    public function getMetadataArguments()
    {
        $args = parent::getMetadataArguments();
        $bindingParameter = $args[$this->getBindingParameterName()];
        list ($repositoryClass) = $this->getCallable();

        /** @var RepositoryInterface $repository */
        $repository = new $repositoryClass;
        $args[$this->getBindingParameterName()] = (new EntityArgument($this, $bindingParameter->getParameter()))
            ->setType(Lodata::getEntityType(EloquentEntitySet::getTypeName($repository->getClass())));

        return $args;
    }

    public function resolveParameter(Argument $argument, $parameter)
    {
        if ($argument->getName() === $this->getBindingParameterName()) {
            return $parameter->getSource();
        }

        return parent::resolveParameter($argument, $parameter);
    }
}