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
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\StreamInterface;
use Flat3\Lodata\NavigationBinding;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\Operation;
use Flat3\Lodata\PathSegment\Metadata;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\ReferentialConstraint;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Transaction\MediaType;
use Flat3\Lodata\Type\Boolean;
use Flat3\Lodata\Type\EnumMember;
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
        $entityContainer->addAttribute('Name', 'DefaultContainer');

        foreach (Lodata::getTypeDefinitions() as $typeDefinition) {
            switch (true) {
                case $typeDefinition instanceof EnumerationType:
                    $typeDefinitionElement = $schema->addChild('EnumType');
                    $typeDefinitionElement->addAttribute('Name', $typeDefinition->getResolvedName($namespace));

                    $typeDefinitionElement->addAttribute(
                        'UnderlyingType',
                        $typeDefinition->getUnderlyingType()->getResolvedName($namespace)
                    );

                    $typeDefinitionElement->addAttribute(
                        'IsFlags',
                        (new Boolean($typeDefinition->getIsFlags()))->toUrl()
                    );

                    /** @var EnumMember $memberValue */
                    foreach ($typeDefinition->getMembers() as $memberName => $memberValue) {
                        $memberElement = $typeDefinitionElement->addChild('Member');
                        $memberElement->addAttribute('Name', $memberName);
                        $memberElement->addAttribute('Value', (string) $memberValue->getValue()->get());
                    }
                    break;

                case $typeDefinition instanceof PrimitiveType:
                    $typeDefinitionElement = $schema->addChild('TypeDefinition');
                    $typeDefinitionElement->addAttribute('Name', $typeDefinition->getResolvedName($namespace));

                    $typeDefinitionElement->addAttribute(
                        'UnderlyingType',
                        $typeDefinition->getUnderlyingType()->getResolvedName($namespace)
                    );
                    break;
            }
        }

        // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EntityType
        foreach (Lodata::getComplexTypes() as $complexType) {
            $complexTypeElement = $schema->addChild($complexType instanceof EntityType ? 'EntityType' : 'ComplexType');
            $complexTypeElement->addAttribute('Name', $complexType->getResolvedName($namespace));

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
            foreach (ObjectArray::merge(
                $complexType->getDeclaredProperties(),
                $complexType->getGeneratedProperties()
            ) as $property) {
                $entityTypeProperty = $complexTypeElement->addChild('Property');
                $entityTypeProperty->addAttribute('Name', $property->getName());

                // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Type
                $entityTypeProperty->addAttribute('Type', $property->getType()->getIdentifier());

                // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_TypeFacets
                $entityTypeProperty->addAttribute(
                    'Nullable',
                    (new Boolean($property->isNullable()))->toUrl()
                );

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
                $navigationPropertyType = $targetComplexType->getIdentifier();
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
            }
        }

        foreach (Lodata::getResources() as $resource) {
            $resourceElement = null;

            switch (true) {
                case $resource instanceof Singleton:
                    // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530395
                    $resourceElement = $entityContainer->addChild('Singleton');
                    $resourceElement->addAttribute('Name', $resource->getResolvedName($namespace));
                    $resourceElement->addAttribute('Type', $resource->getType()->getIdentifier());
                    break;

                case $resource instanceof EntitySet:
                    // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EntitySet
                    $resourceElement = $entityContainer->addChild('EntitySet');
                    $resourceElement->addAttribute('Name', $resource->getResolvedName($namespace));
                    $resourceElement->addAttribute(
                        'EntityType',
                        $resource->getType()->getIdentifier()
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
                            $binding->getTarget()->getResolvedName($namespace)
                        );
                    }
                    break;

                /** @var Operation $resource */
                case $resource instanceof Operation:
                    $isBound = $resource->isBound();

                    $resourceElement = $schema->addChild($resource->getKind());
                    $resourceElement->addAttribute('Name', $resource->getResolvedName($namespace));
                    $resourceElement->addAttribute('IsBound', (new Boolean($isBound))->toUrl());

                    foreach ($resource->getMetadataArguments() as $argument) {
                        $parameterElement = $resourceElement->addChild('Parameter');
                        $parameterElement->addAttribute('Name', $argument->getName());
                        $parameterElement->addAttribute('Type', $argument->getType()->getIdentifier());
                        $parameterElement->addAttribute(
                            'Nullable',
                            (new Boolean($argument->isNullable()))->toUrl()
                        );
                    }

                    $returnType = $resource->getReturnType();
                    if (null !== $returnType) {
                        $returnTypeElement = $resourceElement->addChild('ReturnType');

                        if ($resource->returnsCollection()) {
                            $returnTypeElement->addAttribute('Type',
                                'Collection('.$returnType->getIdentifier().')');
                        } else {
                            $returnTypeElement->addAttribute('Type', $returnType->getIdentifier());
                        }

                        $returnTypeElement->addAttribute(
                            'Nullable',
                            (new Boolean($resource->isNullable()))->toUrl()
                        );
                    }

                    if (!$isBound) {
                        $operationImportElement = $entityContainer->addChild($resource->getKind().'Import');
                        $operationImportElement->addAttribute('Name', $resource->getName());
                        $operationImportElement->addAttribute($resource->getKind(), $resource->getIdentifier());

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
