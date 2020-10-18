<?php

namespace Flat3\Lodata\PathComponent;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Interfaces\EmitInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Model;
use Flat3\Lodata\NavigationBinding;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Operation\Argument;
use Flat3\Lodata\Operation\EntityArgument;
use Flat3\Lodata\Operation\EntitySetArgument;
use Flat3\Lodata\Operation\PrimitiveTypeArgument;
use Flat3\Lodata\Property;
use Flat3\Lodata\ReferentialConstraint;
use Flat3\Lodata\Type\Boolean;
use Illuminate\Http\Request;
use SimpleXMLElement;

class Metadata implements PipeInterface, EmitInterface
{
    public static function pipe(
        Transaction $transaction,
        string $currentComponent,
        ?string $nextComponent,
        ?PipeInterface $argument
    ): PipeInterface {
        if ($currentComponent !== '$metadata') {
            throw new PathNotHandledException();
        }

        if ($argument) {
            throw new BadRequestException('metadata_argument', 'Metadata must be the first argument in the path');
        }

        return new static();
    }

    public function emit(Transaction $transaction): void
    {
        $model = Model::get();

        // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_CSDLXMLDocument
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><edmx:Edmx xmlns:edmx="http://docs.oasis-open.org/odata/ns/edmx" />');
        $version = $transaction->getVersion();
        $root->addAttribute('Version', $version);

        /** @var Annotation\Reference $reference */
        foreach ($model->getModel()->sliceByClass(Annotation\Reference::class) as $reference) {
            $reference->append($root);
        }

        $dataServices = $root->addChild('DataServices');
        $namespace = $model->getNamespace();

        // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Schema
        $schema = $dataServices->addChild('Schema', null, 'http://docs.oasis-open.org/odata/ns/edm');
        $schema->addAttribute('Namespace', $namespace);

        // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EntityContainer
        $entityContainer = $schema->addChild('EntityContainer');
        $entityContainer->addAttribute('Name', 'DefaultContainer');

        // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EntityType
        /** @var EntityType $entityType */
        foreach ($model->getEntityTypes() as $entityType) {
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
            /** @var Property $property */
            foreach ($entityType->getDeclaredProperties() as $property) {
                $entityTypeProperty = $entityTypeElement->addChild('Property');
                $entityTypeProperty->addAttribute('Name', $property->getName());

                // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Type
                $entityTypeProperty->addAttribute('Type', $property->getType()->getResolvedName($namespace));

                // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_TypeFacets
                $entityTypeProperty->addAttribute(
                    'Nullable',
                    Boolean::factory($property->isNullable())->toUrl()
                );
            }

            // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530365
            /** @var NavigationProperty $navigationProperty */
            foreach ($entityType->getNavigationProperties() as $navigationProperty) {
                $targetEntityType = $navigationProperty->getType();

                $navigationPropertyElement = $entityTypeElement->addChild('NavigationProperty');
                $navigationPropertyElement->addAttribute('Name', $navigationProperty->getName());
                $navigationPropertyType = $targetEntityType->getResolvedName($namespace);
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

        foreach ($model->getResources() as $resource) {
            switch (true) {
                case $resource instanceof EntitySet:
                    // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EntitySet
                    $entitySetElement = $entityContainer->addChild('EntitySet');
                    $entitySetElement->addAttribute('Name', $resource->getResolvedName($namespace));
                    $entitySetElement->addAttribute(
                        'EntityType',
                        $resource->getType()->getIdentifier()
                    );

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
                        if ($argument instanceof PrimitiveTypeArgument) {
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

                    $returnType = $resource->getType();
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

        /** @var Annotation $annotation */
        foreach ($model->getModel()->sliceByClass(Annotation::class) as $annotation) {
            $annotation->append($schemaAnnotations);
        }

        $transaction->outputRaw($root->asXML());
    }

    public function response(Transaction $transaction): Response
    {
        $transaction->ensureMethod(Request::METHOD_GET);
        $transaction->configureXmlResponse();

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emit($transaction);
        });
    }
}
