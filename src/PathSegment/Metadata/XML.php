<?php

declare(strict_types=1);

namespace Flat3\Lodata\PathSegment\Metadata;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\EnumerationType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\CollectionType;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\EnumMember;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\StreamInterface;
use Flat3\Lodata\NavigationBinding;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\Operation;
use Flat3\Lodata\PathSegment\Metadata;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Property;
use Flat3\Lodata\ReferentialConstraint;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Transaction\MediaType;
use Flat3\Lodata\Transaction\Version;
use Flat3\Lodata\Type\Boolean;
use SimpleXMLElement;

/**
 * XML
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html
 * @package Flat3\Lodata\PathSegment\Metadata
 */
class XML extends Metadata implements StreamInterface
{
    /**
     * Emit the service metadata document
     * @param  Transaction  $transaction  Transaction
     */
    public function emitStream(Transaction $transaction): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><edmx:Edmx xmlns:edmx="http://docs.oasis-open.org/odata/ns/edmx" />');
        $version = $transaction->getVersion();
        $root->addAttribute('Version', $version);

        foreach (Lodata::getReferences() as $reference) {
            $reference->appendXml($root);
        }

        $dataServices = $root->addChild('DataServices');
        $namespace = Lodata::getNamespace();

        // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Schema
        $schema = $dataServices->addChild('Schema', null, 'http://docs.oasis-open.org/odata/ns/edm');
        $schema->addAttribute('Namespace', $namespace);

        // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EntityContainer
        $entityContainer = $schema->addChild('EntityContainer');
        $entityContainer->addAttribute('Name', Lodata::getEntityContainer()->getName());

        foreach (Lodata::getTypeDefinitions() as $typeDefinition) {
            switch (true) {
                case $typeDefinition instanceof EnumerationType:
                    $members = $typeDefinition->getMembers();

                    if ($members->isEmpty()) {
                        break;
                    }

                    $typeDefinitionElement = $schema->addChild('EnumType');
                    $typeDefinitionElement->addAttribute(
                        'Name',
                        $typeDefinition->getIdentifier()->getResolvedName($namespace)
                    );

                    $typeDefinitionElement->addAttribute(
                        'UnderlyingType',
                        $typeDefinition->getUnderlyingType()->getIdentifier()->getResolvedName($namespace)
                    );

                    $typeDefinitionElement->addAttribute(
                        'IsFlags',
                        (new Boolean($typeDefinition->getIsFlags()))->toUrl()
                    );

                    /** @var EnumMember $memberValue */
                    foreach ($members as $memberValue) {
                        $memberElement = $typeDefinitionElement->addChild('Member');
                        $memberElement->addAttribute('Name', $memberValue->getName());
                        $memberElement->addAttribute('Value', (string) $memberValue->getValue());

                        foreach ($memberValue->getAnnotations() as $annotation) {
                            $annotation->appendXml($memberElement);
                        }
                    }
                    break;

                case $typeDefinition instanceof PrimitiveType:
                    $typeDefinitionElement = $schema->addChild('TypeDefinition');
                    $typeDefinitionElement->addAttribute(
                        'Name',
                        $typeDefinition->getIdentifier()->getResolvedName($namespace)
                    );

                    $typeDefinitionElement->addAttribute(
                        'UnderlyingType',
                        $typeDefinition->getUnderlyingType()->getIdentifier()->getResolvedName($namespace)
                    );
                    break;
            }
        }

        // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EntityType
        foreach (Lodata::getComplexTypes() as $complexType) {
            $complexTypeElement = $schema->addChild($complexType instanceof EntityType ? 'EntityType' : 'ComplexType');
            $complexTypeElement->addAttribute('Name', $complexType->getIdentifier()->getName());

            if ($complexType->isOpen()) {
                $complexTypeElement->addAttribute('OpenType', Constants::true);
            }

            if ($complexType instanceof EntityType) {
                // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Key
                $keyField = $complexType->getKey();

                if ($keyField) {
                    $entityTypeKey = $complexTypeElement->addChild('Key');
                    $entityTypeKeyPropertyRef = $entityTypeKey->addChild('PropertyRef');
                    $entityTypeKeyPropertyRef->addAttribute('Name', $keyField->getName());
                }
            }

            // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_StructuralProperty
            /** @var Property $property */
            foreach (ObjectArray::merge(
                $complexType->getDeclaredProperties(),
                $complexType->getGeneratedProperties()
            ) as $property) {
                $entityTypeProperty = $complexTypeElement->addChild('Property');
                $entityTypeProperty->addAttribute('Name', $property->getName());

                // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Type
                $propertyType = $property->getType();
                $propertyTypeName = $propertyType->getIdentifier()->getQualifiedName();

                if ($propertyType instanceof CollectionType) {
                    $propertyTypeName = sprintf('Collection(%s)', $propertyTypeName);
                }

                $entityTypeProperty->addAttribute('Type', $propertyTypeName);

                // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_TypeFacets
                $entityTypeProperty->addAttribute(
                    'Nullable',
                    (new Boolean(!$propertyType instanceof CollectionType && $property->isNullable()))->toUrl()
                );

                if ($property->hasStaticDefaultValue()) {
                    $entityTypeProperty->addAttribute(
                        'DefaultValue',
                        (string) $property->computeDefaultValue()->toJson()
                    );
                }

                if ($property->hasPrecision()) {
                    $entityTypeProperty->addAttribute('Precision', (string) $property->getPrecision());
                }

                if ($property->hasMaxLength()) {
                    $entityTypeProperty->addAttribute('MaxLength', (string) $property->getMaxLength());
                }

                if ($property->hasScale()) {
                    $scale = $property->getScale();

                    if ($scale !== Constants::floating || $transaction->getVersion() !== Version::v4_0) {
                        $entityTypeProperty->addAttribute('Scale', (string) $scale);
                    }
                }

                foreach ($property->getAnnotations() as $annotation) {
                    $annotation->appendXml($entityTypeProperty);
                }
            }

            // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530365
            /** @var NavigationProperty $navigationProperty */
            foreach ($complexType->getNavigationProperties() as $navigationProperty) {
                /** @var ComplexType $targetComplexType */
                $targetComplexType = $navigationProperty->getType();

                $navigationPropertyElement = $complexTypeElement->addChild('NavigationProperty');
                $navigationPropertyElement->addAttribute('Name', $navigationProperty->getName());
                $navigationPropertyType = $targetComplexType->getIdentifier()->getQualifiedName();
                if ($navigationProperty->isCollection()) {
                    $navigationPropertyType = 'Collection('.$navigationPropertyType.')';
                }

                $navigationPropertyPartner = $navigationProperty->getPartner();
                if ($navigationPropertyPartner) {
                    $navigationPropertyElement->addAttribute(
                        'Partner',
                        $navigationPropertyPartner->getName()
                    );
                }

                $navigationPropertyElement->addAttribute('Type', $navigationPropertyType);
                $navigationPropertyElement->addAttribute(
                    'Nullable',
                    (new Boolean($navigationProperty->isNullable()))->toUrl()
                );

                /** @var ReferentialConstraint $constraint */
                foreach ($navigationProperty->getConstraints() as $constraint) {
                    $referentialConstraint = $navigationPropertyElement->addChild('ReferentialConstraint');
                    $referentialConstraint->addAttribute('Property', $constraint->getProperty()->getName());
                    $referentialConstraint->addAttribute(
                        'ReferencedProperty',
                        $constraint->getReferencedProperty()->getName()
                    );
                }

                foreach ($navigationProperty->getAnnotations() as $annotation) {
                    $annotation->appendXml($navigationPropertyElement);
                }
            }
        }

        foreach (Lodata::getResources() as $resource) {
            $resourceElement = null;

            switch (true) {
                case $resource instanceof Singleton:
                    // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530395
                    $resourceElement = $entityContainer->addChild('Singleton');
                    $resourceElement->addAttribute('Name', $resource->getIdentifier()->getResolvedName($namespace));
                    $resourceElement->addAttribute('Type', $resource->getType()->getIdentifier()->getQualifiedName());
                    break;

                case $resource instanceof EntitySet:
                    // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EntitySet
                    $resourceElement = $entityContainer->addChild('EntitySet');
                    $resourceElement->addAttribute('Name', $resource->getIdentifier()->getResolvedName($namespace));
                    $resourceElement->addAttribute(
                        'EntityType',
                        $resource->getType()->getIdentifier()->getQualifiedName()
                    );

                    // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_NavigationPropertyBinding
                    /** @var NavigationBinding $binding */
                    foreach ($resource->getNavigationBindings() as $binding) {
                        $navigationPropertyBindingElement = $resourceElement->addChild('NavigationPropertyBinding');
                        $navigationPropertyBindingElement->addAttribute(
                            'Path',
                            $binding->getPath()->getName()
                        );
                        $navigationPropertyBindingElement->addAttribute(
                            'Target',
                            $binding->getTarget()->getIdentifier()->getResolvedName($namespace)
                        );
                    }
                    break;

                /** @var Operation $resource */
                case $resource instanceof Operation:
                    $isBound = $resource->isBound();

                    $resourceElement = $schema->addChild($resource->getKind());
                    $resourceElement->addAttribute('Name', $resource->getIdentifier()->getName());
                    $resourceElement->addAttribute('IsBound', (new Boolean($isBound))->toUrl());

                    foreach ($resource->getMetadataArguments() as $argument) {
                        $parameterElement = $resourceElement->addChild('Parameter');
                        $parameterElement->addAttribute('Name', $argument->getName());
                        $argumentType = $argument->getType();
                        $argumentTypeName = $argumentType->getIdentifier()->getQualifiedName();

                        if ($argumentType instanceof CollectionType) {
                            $argumentTypeName = sprintf('Collection(%s)', $argumentTypeName);
                        }

                        $parameterElement->addAttribute('Type', $argumentTypeName);
                        $parameterElement->addAttribute(
                            'Nullable',
                            (new Boolean($argument->isNullable()))->toUrl()
                        );
                    }

                    $returnType = $resource->getReturnType();
                    if (null !== $returnType) {
                        $returnTypeElement = $resourceElement->addChild('ReturnType');

                        if ($resource->returnsCollection()) {
                            $returnTypeElement->addAttribute(
                                'Type',
                                sprintf("Collection(%s)", $returnType->getIdentifier()->getQualifiedName())
                            );
                        } else {
                            $returnTypeElement->addAttribute('Type', $returnType->getIdentifier()->getQualifiedName());
                        }

                        $returnTypeElement->addAttribute(
                            'Nullable',
                            (new Boolean($resource->isNullable()))->toUrl()
                        );
                    }

                    if (!$isBound) {
                        $operationImportElement = $entityContainer->addChild($resource->getKind().'Import');
                        $operationImportElement->addAttribute('Name', $resource->getName());
                        $operationImportElement->addAttribute(
                            $resource->getKind(),
                            $resource->getIdentifier()->getQualifiedName()
                        );

                        if (null !== $returnType && $returnType instanceof EntitySet) {
                            $operationImportElement->addAttribute('EntitySet', $returnType->getName());
                        }
                    }
                    break;
            }

            // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530341
            if ($resource instanceof AnnotationInterface) {
                /** @var Annotation $annotation */
                foreach ($resource->getAnnotations() as $annotation) {
                    $annotation->appendXml($resourceElement);
                }
            }
        }

        if (!$entityContainer->count()) {
            unset($entityContainer[0]);
        }

        $schemaAnnotations = $schema->addChild('Annotations');
        $schemaAnnotations->addAttribute('Target', $namespace.'.'.'DefaultContainer');

        foreach (Lodata::getAnnotations() as $annotation) {
            $annotation->appendXml($schemaAnnotations);
        }

        if (!$schemaAnnotations->count()) {
            unset($schemaAnnotations[0]);
        }

        $transaction->sendOutput($root->asXML());
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $transaction->sendContentType((new MediaType)->parse(MediaType::xml));

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emitStream($transaction);
        });
    }
}
