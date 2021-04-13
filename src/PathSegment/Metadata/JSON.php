<?php

namespace Flat3\Lodata\PathSegment\Metadata;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\JsonInterface;
use Flat3\Lodata\Interfaces\Operation\ActionInterface;
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\PathSegment\Metadata;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Transaction\MediaType;
use stdClass;

/**
 * JSON
 * @link http://docs.oasis-open.org/odata/odata-csdl-json/v4.01/odata-csdl-json-v4.01.html
 * @package Flat3\Lodata\PathSegment\Metadata
 */
class JSON extends Metadata implements JsonInterface
{
    /**
     * Emit the service metadata document
     * @param  Transaction  $transaction  Transaction
     */
    public function emitJson(Transaction $transaction): void
    {
        $root = new stdClass();

        $version = $transaction->getVersion();
        $namespace = Lodata::getNamespace();

        $root->{'$Version'} = $version;
        $root->{'$EntityContainer'} = $namespace.'.'.'DefaultContainer';

        $root->{'$Reference'} = [];
        foreach (Lodata::getReferences() as $reference) {
            $reference->appendJson($root);
        }

        $schema = new stdClass();
        $root->{$namespace} = $schema;

        foreach (Lodata::getEntityTypes() as $entityType) {
            $entityTypeElement = new stdClass();
            $schema->{$entityType->getName()} = $entityTypeElement;
            $entityTypeElement->{'$Kind'} = 'EntityType';

            $keyField = $entityType->getKey();

            if ($keyField) {
                $entityTypeElement->{'$Key'} = [
                    $keyField->getName(),
                ];
            }

            foreach ($entityType->getDeclaredProperties() as $property) {
                $entityTypeProperty = new stdClass();
                $entityTypeElement->{$property->getName()} = $entityTypeProperty;
                $entityTypeProperty->{'$Type'} = $property->getType()->getIdentifier();
                $entityTypeProperty->{'$Nullable'} = $property->isNullable();

                foreach ($property->getAnnotations() as $annotation) {
                    $annotation->appendJson($entityTypeProperty);
                }
            }

            foreach ($entityType->getNavigationProperties() as $navigationProperty) {
                $targetEntityType = $navigationProperty->getType();

                $navigationPropertyElement = new stdClass();
                $entityTypeElement->{$navigationProperty->getName()} = $navigationPropertyElement;
                $navigationPropertyElement->{'$Collection'} = $navigationProperty->isCollection();

                $navigationPropertyPartner = $navigationProperty->getPartner();
                if ($navigationPropertyPartner) {
                    $navigationPropertyElement->{'$Partner'} = $navigationPropertyPartner->getName();
                }

                $navigationPropertyElement->{'$Type'} = $targetEntityType->getIdentifier();
                $navigationPropertyElement->{'$Nullable'} = $navigationProperty->isNullable();

                $constraints = $navigationProperty->getConstraints();
                if ($constraints) {
                    $constraintsElement = new stdClass();
                    $navigationPropertyElement->{'$ReferentialConstraint'} = $constraintsElement;
                    foreach ($navigationProperty->getConstraints() as $constraint) {
                        $constraintsElement->{$constraint->getProperty()->getName()} = $constraint->getReferencedProperty()->getName();
                    }
                }
            }
        }

        foreach (Lodata::getEnumerationTypes() as $enumerationType) {
            $enumerationTypeElement = new stdClass();
            $schema->{$enumerationType->getName()} = $enumerationTypeElement;

            $enumerationTypeElement->{'$UnderlyingType'} = $enumerationType->getUnderlyingType()->getResolvedName($namespace);
            $enumerationTypeElement->{'$IsFlags'} = $enumerationType->getIsFlags();

            foreach ($enumerationType->getMembers() as $memberName => $memberValue) {
                $enumerationTypeElement->{$memberName} = $memberValue;
            }
        }

        foreach (Lodata::getResources() as $resource) {
            $resourceElement = new stdClass();

            switch (true) {
                case $resource instanceof Singleton:
                    $schema->{$resource->getResolvedName($namespace)} = [
                        '$Type' => $resource->getType()->getIdentifier(),
                    ];
                    break;

                case $resource instanceof EntitySet:
                    $schema->{$resource->getResolvedName($namespace)} = $resourceElement;
                    $resourceElement->{'$EntityType'} = $resource->getType()->getIdentifier();

                    $navigationBindings = $resource->getNavigationBindings();
                    if ($navigationBindings) {
                        $navigationPropertyBindingElement = new stdClass();
                        $resourceElement->{'$NavigationPropertyBinding'} = $navigationPropertyBindingElement;

                        foreach ($resource->getNavigationBindings() as $binding) {
                            $navigationPropertyBindingElement->{$binding->getPath()->getName()} = $binding->getTarget()->getResolvedName($namespace);
                        }
                    }
                    break;

                case $resource instanceof Operation:
                    $schema->{$resource->getResolvedName($namespace)} = $resourceElement;
                    $resourceElement->{'$Kind'} = $resource->getKind();

                    if ($resource->getBindingParameterName()) {
                        $resourceElement->{'$IsBound'} = true;
                    }

                    $arguments = $this->getOperationArguments($resource);

                    if ($arguments) {
                        $argumentsElement = [];
                        foreach ($arguments as $argument) {
                            $argumentsElement[] = [
                                '$Name' => $argument->getName(),
                                '$Nullable' => $argument->isNullable(),
                                '$Type' => $argument->getType()->getIdentifier(),
                            ];
                        }
                        $resourceElement->{'$Parameter'} = $argumentsElement;
                    }

                    $returnType = $resource->getReturnType();

                    if (null !== $returnType) {
                        $returnTypeElement = new stdClass();
                        $resourceElement->{'$ReturnType'} = $returnTypeElement;
                        $returnTypeElement->{'$Collection'} = $resource->returnsCollection();
                        $returnTypeElement->{'$Type'} = $returnType->getIdentifier();
                        $returnTypeElement->{'$Nullable'} = $resource->isNullable();
                    }

                    /** @var Operation $resource */
                    if ($resource instanceof FunctionInterface) {
                        $operationImportElement = new stdClass();
                        $schema->{$resource->getResolvedName($namespace).'Import'} = $operationImportElement;
                        $operationImportElement->{$resource instanceof ActionInterface ? '$Action' : '$Function'} = $resource->getIdentifier();
                    }
                    break;
            }

            if ($resource instanceof AnnotationInterface) {
                foreach ($resource->getAnnotations() as $annotation) {
                    $annotation->appendJson($resourceElement);
                }
            }
        }

        $schemaAnnotationsElement = new stdClass();
        $schema->{'$Annotations'} = $schemaAnnotationsElement;

        $targetElement = new stdClass();
        $schemaAnnotationsElement->{$namespace.'.'.'DefaultContainer'} = $targetElement;

        foreach (Lodata::getAnnotations() as $annotation) {
            $annotation->appendJson($targetElement);
        }

        $transaction->sendJson($root);
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $transaction->sendContentType(MediaType::factory()->parse('application/json'));

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emitJson($transaction);
        });
    }
}
