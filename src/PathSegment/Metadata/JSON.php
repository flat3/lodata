<?php

declare(strict_types=1);

namespace Flat3\Lodata\PathSegment\Metadata;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\EnumerationType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\CollectionType;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\JsonInterface;
use Flat3\Lodata\Interfaces\ResponseInterface;
use Flat3\Lodata\Model;
use Flat3\Lodata\Operation;
use Flat3\Lodata\PathSegment\Metadata;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Property;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Transaction\MediaType;
use Flat3\Lodata\Transaction\Version;

/**
 * JSON
 * @link https://docs.oasis-open.org/odata/odata-csdl-json/v4.01/odata-csdl-json-v4.01.html
 * @package Flat3\Lodata\PathSegment\Metadata
 */
class JSON extends Metadata implements ResponseInterface, JsonInterface
{
    /**
     * Emit the service metadata document
     * @param  Transaction  $transaction  Transaction
     */
    public function emitJson(Transaction $transaction): void
    {
        $schema = (object) [];

        $version = $transaction->getVersion();
        $namespace = Model::getNamespace();

        $schema->{'$Version'} = $version;
        $schema->{'$EntityContainer'} = (string) Lodata::getEntityContainer()->getIdentifier();

        $schema->{'$Reference'} = [];
        foreach (Lodata::getReferences() as $reference) {
            $reference->appendJson($schema);
        }

        $entityContainer = (object) [];
        $entityContainer->{'$Kind'} = 'EntityContainer';
        $schema->{$namespace} = $entityContainer;

        foreach (Lodata::getTypeDefinitions() as $typeDefinition) {
            $typeDefinitionElement = (object) [];

            switch (true) {
                case $typeDefinition instanceof EnumerationType:
                    $typeDefinitionElement->{'$Kind'} = 'EnumType';
                    $typeDefinitionElement->{'$IsFlags'} = $typeDefinition->getIsFlags();

                    foreach ($typeDefinition->getMembers() as $member) {
                        $typeDefinitionElement->{$member->getName()} = $member->getValue();

                        foreach ($member->getAnnotations() as $annotation) {
                            $annotation->appendJson($typeDefinitionElement);
                        }
                    }
                    break;

                case $typeDefinition instanceof PrimitiveType:
                    $typeDefinitionElement->{'$Kind'} = 'TypeDefinition';
                    break;
            }

            if ($typeDefinition instanceof PrimitiveType) {
                $typeDefinitionElement->{'$UnderlyingType'} = $typeDefinition->getUnderlyingType()->getIdentifier()->getResolvedName($namespace);
            }

            $schema->{$typeDefinition->getIdentifier()->getResolvedName($namespace)} = $typeDefinitionElement;
        }

        foreach (Lodata::getComplexTypes() as $complexType) {
            $complexTypeElement = (object) [];
            $schema->{$complexType->getName()} = $complexTypeElement;

            $complexTypeElement->{'$Kind'} = $complexType instanceof EntityType ? 'EntityType' : 'ComplexType';

            if ($complexType->isOpen()) {
                $complexTypeElement->{'$OpenType'} = true;
            }

            if ($complexType instanceof EntityType) {
                $keyField = $complexType->getKey();

                if ($keyField) {
                    $complexTypeElement->{'$Key'} = [
                        $keyField->getName(),
                    ];
                }
            }

            /** @var Property $property */
            foreach (ObjectArray::merge(
                $complexType->getDeclaredProperties(),
                $complexType->getGeneratedProperties()
            ) as $property) {
                $complexTypeProperty = (object) [];
                $complexTypeElement->{$property->getName()} = $complexTypeProperty;
                $propertyType = $property->getType();

                if ($propertyType instanceof CollectionType) {
                    $complexTypeProperty->{'$Collection'} = true;
                }

                $complexTypeProperty->{'$Type'} = $propertyType->getIdentifier()->getQualifiedName();
                $complexTypeProperty->{'$Nullable'} = !$propertyType instanceof CollectionType && $property->isNullable();

                if ($property->hasStaticDefaultValue()) {
                    $complexTypeProperty->{'$DefaultValue'} = $property->computeDefaultValue()->toJson();
                }

                if ($property->hasMaxLength()) {
                    $complexTypeProperty->{'$MaxLength'} = $property->getMaxLength();
                }

                if ($property->hasPrecision()) {
                    $complexTypeProperty->{'$Precision'} = $property->getPrecision();
                }

                if ($property->hasScale()) {
                    $scale = $property->getScale();

                    if ($scale !== Constants::floating || $transaction->getVersion() !== Version::v4_0) {
                        $complexTypeProperty->{'$Scale'} = $scale;
                    }
                }

                foreach ($property->getAnnotations() as $annotation) {
                    $annotation->appendJson($complexTypeProperty);
                }
            }

            foreach ($complexType->getNavigationProperties() as $navigationProperty) {
                $targetComplexType = $navigationProperty->getType();

                $navigationPropertyElement = (object) [];
                $complexTypeElement->{$navigationProperty->getName()} = $navigationPropertyElement;
                $navigationPropertyElement->{'$Collection'} = $navigationProperty->isCollection();

                $navigationPropertyPartner = $navigationProperty->getPartner();
                if ($navigationPropertyPartner) {
                    $navigationPropertyElement->{'$Partner'} = $navigationPropertyPartner->getName();
                }

                $navigationPropertyElement->{'$Type'} = $targetComplexType->getIdentifier()->getQualifiedName();
                $navigationPropertyElement->{'$Nullable'} = $navigationProperty->isNullable();

                $constraints = $navigationProperty->getConstraints();
                if ($constraints->hasEntries()) {
                    $constraintsElement = (object) [];
                    $navigationPropertyElement->{'$ReferentialConstraint'} = $constraintsElement;
                    foreach ($navigationProperty->getConstraints() as $constraint) {
                        $constraintsElement->{$constraint->getProperty()->getName()} = $constraint->getReferencedProperty()->getName();
                    }
                }

                foreach ($navigationProperty->getAnnotations() as $annotation) {
                    $annotation->appendJson($navigationPropertyElement);
                }
            }
        }

        foreach (Lodata::getResources() as $resource) {
            $resourceElement = (object) [];

            switch (true) {
                case $resource instanceof Singleton:
                    $entityContainer->{$resource->getIdentifier()->getResolvedName($namespace)} = $resourceElement;
                    $resourceElement->{'$Type'} = $resource->getType()->getIdentifier()->getQualifiedName();
                    break;

                case $resource instanceof EntitySet:
                    $entityContainer->{$resource->getIdentifier()->getResolvedName($namespace)} = $resourceElement;
                    $resourceElement->{'$EntityType'} = $resource->getType()->getIdentifier()->getQualifiedName();

                    $navigationBindings = $resource->getNavigationBindings();
                    if ($navigationBindings->hasEntries()) {
                        $navigationPropertyBindingElement = (object) [];
                        $resourceElement->{'$NavigationPropertyBinding'} = $navigationPropertyBindingElement;

                        foreach ($resource->getNavigationBindings() as $binding) {
                            $navigationPropertyBindingElement->{$binding->getPath()->getName()} = $binding->getTarget()->getIdentifier()->getResolvedName($namespace);
                        }
                    }
                    break;

                case $resource instanceof Operation:
                    $isBound = $resource->isBound();

                    $schema->{$resource->getIdentifier()->getResolvedName($namespace)} = $resourceElement;
                    $resourceElement->{'$Kind'} = $resource->getKind();
                    $resourceElement->{'$IsBound'} = $isBound;

                    $arguments = $resource->getMetadataArguments();

                    if ($arguments->hasEntries()) {
                        $argumentsElement = [];
                        foreach ($arguments as $argument) {
                            $argumentType = $argument->getType();

                            $argumentsElement[] = [
                                '$Name' => $argument->getName(),
                                '$Nullable' => !$argumentType instanceof CollectionType && $argument->isNullable(),
                                '$Type' => $argumentType->getIdentifier()->getQualifiedName(),
                            ];

                            if ($argumentType instanceof CollectionType) {
                                $argumentsElement['$Collection'] = true;
                            }
                        }
                        $resourceElement->{'$Parameter'} = $argumentsElement;
                    }

                    $returnType = $resource->getReturnType();

                    if (null !== $returnType) {
                        $returnTypeElement = (object) [];
                        $resourceElement->{'$ReturnType'} = $returnTypeElement;
                        $returnTypeElement->{'$Collection'} = $resource->returnsCollection();
                        $returnTypeElement->{'$Type'} = $returnType->getIdentifier()->getQualifiedName();
                        $returnTypeElement->{'$Nullable'} = $resource->isNullable();
                    }

                    if (!$isBound) {
                        $operationImportElement = (object) [];
                        $entityContainer->{$resource->getIdentifier()->getResolvedName($namespace).'Import'} = $operationImportElement;
                        $operationImportElement->{$resource->isAction() ? '$Action' : '$Function'} = $resource->getIdentifier()->getQualifiedName();

                        if ($returnType instanceof EntitySet) {
                            $operationImportElement->{'$EntitySet'} = $returnType->getName();
                        }
                    }
                    break;
            }

            if ($resource instanceof AnnotationInterface) {
                foreach ($resource->getAnnotations() as $annotation) {
                    $annotation->appendJson($resourceElement);
                }
            }
        }

        $schemaAnnotationsElement = (object) [];
        $entityContainer->{'$Annotations'} = $schemaAnnotationsElement;

        $targetElement = (object) [];
        $schemaAnnotationsElement->{$namespace.'.'.'DefaultContainer'} = $targetElement;

        foreach (Lodata::getAnnotations() as $annotation) {
            $annotation->appendJson($targetElement);
        }

        $transaction->sendJson($schema);
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $transaction->sendContentType((new MediaType)->parse(MediaType::json));

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emitJson($transaction);
        });
    }
}
