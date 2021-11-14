<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\ConfigurationException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\RepositoryInterface;
use Flat3\Lodata\Operation;

class Repository extends Operation
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

        $entityType = Lodata::getEntityType(EntityType::convertClassName($repository->getClass()));

        if (!$entityType instanceof EntityType) {
            throw new ConfigurationException(
                'invalid_entity_type',
                'The entity type used by this repository has not been registered'
            );
        }

        $args[$this->getBindingParameterName()] = (new EntityArgument($this, $bindingParameter->getParameter()))
            ->setType($entityType);

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