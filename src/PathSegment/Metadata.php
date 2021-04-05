<?php

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\StreamInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\NavigationBinding;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Operation\Argument;
use Flat3\Lodata\Operation\EntityArgument;
use Flat3\Lodata\Operation\EntitySetArgument;
use Flat3\Lodata\Operation\PrimitiveArgument;
use Flat3\Lodata\ReferentialConstraint;
use Flat3\Lodata\Type\Boolean;
use Flat3\Lodata\Type\EnumMember;
use Illuminate\Http\Request;
use SimpleXMLElement;

/**
 * Metadata
 * @package Flat3\Lodata\PathSegment
 */
class Metadata implements PipeInterface, StreamInterface
{
    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): PipeInterface {
        if ($currentSegment !== '$metadata') {
            throw new PathNotHandledException();
        }

        if ($argument) {
            throw new BadRequestException('metadata_argument', 'Metadata must be the first argument in the path');
        }

        return new self();
    }

    /**
     * Emit the service metadata document
     * @param  Transaction  $transaction  Transaction
     */
    public function emitStream(Transaction $transaction): void
    {
        // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_CSDLXMLDocument
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><edmx:Edmx xmlns:edmx="http://docs.oasis-open.org/odata/ns/edmx" />');
        $version = $transaction->getVersion();
        $root->addAttribute('Version', $version);

        foreach (Lodata::getReferences() as $reference) {
            $reference->append($root);
        }

        $dataServices = $root->addChild('DataServices');
        $namespace = Lodata::getNamespace();

        // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Schema
        $schema = $dataServices->addChild('Schema', null, 'http://docs.oasis-open.org/odata/ns/edm');
        $schema->addAttribute('Namespace', $namespace);

        // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EntityContainer
        $entityContainer = $schema->addChild('EntityContainer');
        $entityContainer->addAttribute('Name', 'DefaultContainer');

        // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EntityType
        foreach (Lodata::getEntityTypes() as $entityType) {
            $entityTypeElement = $schema->addChild('EntityType');
            $entityTypeElement->addAttribute('Name', $entityType->getResolvedName($namespace));

            // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Key
            $keyField = $entityType->getKey();

            if ($keyField) {
                $entityTypeKey = $entityTypeElement->addChild('Key');
                $entityTypeKeyPropertyRef = $entityTypeKey->addChild('PropertyRef');
                $entityTypeKeyPropertyRef->addAttribute('Name', $keyField->getName());
            }

            // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_StructuralProperty
            foreach ($entityType->getDeclaredProperties() as $property) {
                $entityTypeProperty = $entityTypeElement->addChild('Property');
                $entityTypeProperty->addAttribute('Name', $property->getName());

                // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Type
                $entityTypeProperty->addAttribute('Type', $property->getType()->getIdentifier());

                // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_TypeFacets
                $entityTypeProperty->addAttribute(
                    'Nullable',
                    Boolean::factory($property->isNullable())->toUrl()
                );

                foreach ($property->getAnnotations() as $annotation) {
                    $annotation->append($entityTypeProperty);
                }
            }

            // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530365
            /** @var NavigationProperty $navigationProperty */
            foreach ($entityType->getNavigationProperties() as $navigationProperty) {
                /** @var EntityType $targetEntityType */
                $targetEntityType = $navigationProperty->getType();

                $navigationPropertyElement = $entityTypeElement->addChild('NavigationProperty');
                $navigationPropertyElement->addAttribute('Name', $navigationProperty->getName());
                $navigationPropertyType = $targetEntityType->getIdentifier();
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
                    Boolean::factory($navigationProperty->isNullable())->toUrl()
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

        foreach (Lodata::getEnumerationTypes() as $enumerationType) {
            $enumerationTypeElement = $schema->addChild('EnumType');
            $enumerationTypeElement->addAttribute('Name', $enumerationType->getResolvedName($namespace));
            $enumerationTypeElement->addAttribute(
                'UnderlyingType',
                $enumerationType->getUnderlyingType()->getResolvedName($namespace)
            );
            $enumerationTypeElement->addAttribute('IsFlags', Boolean::factory($enumerationType->getIsFlags())->toUrl());

            /** @var EnumMember $memberValue */
            foreach ($enumerationType->getMembers() as $memberName => $memberValue) {
                $memberElement = $enumerationTypeElement->addChild('Member');
                $memberElement->addAttribute('Name', $memberName);
                $memberElement->addAttribute('Value', $memberValue->getValue());
            }
        }

        foreach (Lodata::getResources() as $resource) {
            switch (true) {
                case $resource instanceof EntitySet:
                    // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EntitySet
                    $entitySetElement = $entityContainer->addChild('EntitySet');
                    $entitySetElement->addAttribute('Name', $resource->getResolvedName($namespace));
                    $entitySetElement->addAttribute(
                        'EntityType',
                        $resource->getType()->getIdentifier()
                    );

                    // https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530341
                    if ($resource instanceof AnnotationInterface) {
                        /** @var Annotation $annotation */
                        foreach ($resource->getAnnotations() as $annotation) {
                            $annotation->append($entitySetElement);
                        }
                    }

                    // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_NavigationPropertyBinding
                    /** @var NavigationBinding $binding */
                    foreach ($resource->getNavigationBindings() as $binding) {
                        $navigationPropertyBindingElement = $entitySetElement->addChild('NavigationPropertyBinding');
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
                    $operationElement = $schema->addChild($resource->getKind());
                    $operationElement->addAttribute('Name', $resource->getResolvedName($namespace));
                    if ($resource->getBindingParameterName()) {
                        $operationElement->addAttribute('IsBound', Constants::TRUE);
                    }

                    // Ensure the binding parameter is first, if it exists. Filter out non-odata arguments.
                    $arguments = $resource->getArguments()->sort(function (Argument $a, Argument $b) use ($resource) {
                        if ($a->getName() === $resource->getBindingParameterName()) {
                            return -1;
                        }

                        if ($b->getName() === $resource->getBindingParameterName()) {
                            return 1;
                        }

                        return 0;
                    })->filter(function ($argument) use ($resource) {
                        if ($argument instanceof PrimitiveArgument) {
                            return true;
                        }

                        if (($argument instanceof EntitySetArgument || $argument instanceof EntityArgument) && $resource->getBindingParameterName() === $argument->getName()) {
                            return true;
                        }

                        return false;
                    });

                    /** @var Argument $argument */
                    foreach ($arguments as $argument) {
                        $parameterElement = $operationElement->addChild('Parameter');
                        $parameterElement->addAttribute('Name', $argument->getName());
                        $parameterElement->addAttribute('Type', $argument->getType()->getIdentifier());
                        $parameterElement->addAttribute(
                            'Nullable',
                            Boolean::factory($argument->isNullable())->toUrl()
                        );
                    }

                    $returnType = $resource->getReturnType();
                    if (null !== $returnType) {
                        $returnTypeElement = $operationElement->addChild('ReturnType');

                        if ($resource->returnsCollection()) {
                            $returnTypeElement->addAttribute('Type',
                                'Collection('.$returnType->getIdentifier().')');
                        } else {
                            $returnTypeElement->addAttribute('Type', $returnType->getIdentifier());
                        }

                        $returnTypeElement->addAttribute(
                            'Nullable',
                            Boolean::factory($resource->isNullable())->toUrl()
                        );
                    }

                    $operationImport = $entityContainer->addChild($resource->getKind().'Import');
                    $operationImport->addAttribute('Name', $resource->getResolvedName($namespace));
                    $operationImport->addAttribute(
                        $resource->getKind(),
                        $resource->getResolvedName($namespace)
                    );
                    break;
            }
        }

        $schemaAnnotations = $schema->addChild('Annotations');
        $schemaAnnotations->addAttribute('Target', $namespace.'.'.'DefaultContainer');

        foreach (Lodata::getAnnotations() as $annotation) {
            $annotation->append($schemaAnnotations);
        }

        $transaction->sendOutput($root->asXML());
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $transaction->ensureMethod(Request::METHOD_GET);

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emitStream($transaction);
        });
    }
}
