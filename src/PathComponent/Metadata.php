<?php

namespace Flat3\OData\PathComponent;

use Flat3\OData\Controller\Transaction;
use Flat3\OData\EntitySet;
use Flat3\OData\EntityType;
use Flat3\OData\Exception\Internal\PathNotHandledException;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Helper\Argument;
use Flat3\OData\Interfaces\EmitInterface;
use Flat3\OData\Interfaces\PipeInterface;
use Flat3\OData\Model;
use Flat3\OData\NavigationProperty;
use Flat3\OData\ReferentialConstraint;
use Flat3\OData\Transaction\Metadata\Full;
use Flat3\OData\Transaction\Metadata\Minimal;
use Flat3\OData\Transaction\Metadata\None;
use Flat3\OData\Transaction\ParameterList;
use Flat3\OData\Type\Boolean;
use Flat3\OData\Type\Property;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Metadata implements PipeInterface, EmitInterface
{
    public static function pipe(
        Transaction $transaction,
        string $pathComponent,
        ?PipeInterface $argument
    ): PipeInterface {
        if ($pathComponent !== '$metadata') {
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

        $dataServices = $root->addChild('DataServices');

        // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Schema
        $schema = $dataServices->addChild('Schema', null, 'http://docs.oasis-open.org/odata/ns/edm');
        $schema->addAttribute('Namespace', $model->getNamespace());

        // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EntityContainer
        $entityContainer = $schema->addChild('EntityContainer');
        $entityContainer->addAttribute('Name', 'DefaultContainer');

        // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EntityType
        /** @var EntityType $entityType */
        foreach ($model->getEntityTypes() as $entityType) {
            $entityTypeElement = $schema->addChild('EntityType');
            $entityTypeElement->addAttribute('Name', $entityType->getName());

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
                $entityTypeProperty->addAttribute('Type', $property->getType()->getName());

                // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_TypeFacets
                $entityTypeProperty->addAttribute(
                    'Nullable',
                    Boolean::factory($property->isNullable())->toUrl()
                );
            }

            // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530365
            /** @var \Flat3\OData\NavigationProperty $navigationProperty */
            foreach ($entityType->getNavigationProperties() as $navigationProperty) {
                $targetEntityType = $navigationProperty->getType();

                $navigationPropertyElement = $entityTypeElement->addChild('NavigationProperty');
                $navigationPropertyElement->addAttribute('Name', $navigationProperty->getName());
                $navigationPropertyType = $model->getNamespace().'.'.$targetEntityType->getName();
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
                    $entitySetElement->addAttribute('Name', $resource->getName());
                    $entitySetElement->addAttribute(
                        'EntityType',
                        $model->getNamespace().'.'.$resource->getType()->getName()
                    );

                    // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_NavigationPropertyBinding
                    /** @var \Flat3\OData\NavigationBinding $binding */
                    foreach ($resource->getNavigationBindings() as $binding) {
                        $navigationPropertyBindingElement = $entitySetElement->addChild('NavigationPropertyBinding');
                        $navigationPropertyBindingElement->addAttribute(
                            'Path',
                            $binding->getPath()->getName()
                        );
                        $navigationPropertyBindingElement->addAttribute(
                            'Target',
                            $binding->getTarget()->getName()
                        );
                    }
                    break;

                /** @var Operation $resource */
                case $resource instanceof Operation:
                    $operationElement = $schema->addChild($resource->getKind());
                    $operationElement->addAttribute('Name', $resource->getName());

                    $returnType = $resource->getType();
                    if (null !== $returnType) {
                        $returnTypeElement = $operationElement->addChild('ReturnType');

                        if ($resource->returnsCollection()) {
                            $returnTypeElement->addAttribute('Type', 'Collection('.$returnType->getName().')');
                        } else {
                            $returnTypeElement->addAttribute('Type', $returnType->getName());
                        }

                        $returnTypeElement->addAttribute(
                            'Nullable',
                            Boolean::factory($resource->isNullable())->toUrl()
                        );
                    }

                    /** @var Argument $argument */
                    foreach ($resource->getArguments() as $argument) {
                        $parameterElement = $operationElement->addChild('Parameter');
                        $parameterElement->addAttribute('Name', $argument->getName());
                        $parameterElement->addAttribute('Type', $argument->getType()->getName());
                        $parameterElement->addAttribute(
                            'Nullable',
                            Boolean::factory($argument->isNullable())->toUrl()
                        );
                    }

                    $operationImport = $entityContainer->addChild($resource->getKind().'Import');
                    $operationImport->addAttribute('Name', $resource->getName());
                    $operationImport->addAttribute(
                        $resource->getKind(),
                        $model->getNamespace().'.'.$resource->getName()
                    );
                    break;
            }
        }

        $schemaAnnotations = $schema->addChild('Annotations');
        $schemaAnnotations->addAttribute('Target', $model->getNamespace().'.'.'DefaultContainer');

        $conventionalIds = $schemaAnnotations->addChild('Annotation');
        $conventionalIds->addAttribute('Term', 'Org.OData.Core.V1.ConventionalIDs');
        $conventionalIds->addAttribute('Bool', Boolean::URL_TRUE);

        $dereferencerableIds = $schemaAnnotations->addChild('Annotation');
        $dereferencerableIds->addAttribute('Term', 'Org.OData.Core.V1.DereferenceableIDs');
        $dereferencerableIds->addAttribute('Bool', Boolean::URL_TRUE);

        $conformanceLevel = $schemaAnnotations->addChild('Annotation');
        $conformanceLevel->addAttribute('Term', 'Org.OData.Capabilities.V1.ConformanceLevel');
        $conformanceLevel->addChild(
            'EnumMember',
            'Org.OData.Capabilities.V1.ConformanceLevelType/Advanced'
        );

        $defaultNamespace = $schemaAnnotations->addChild('Annotation');
        $defaultNamespace->addAttribute('Term', 'Org.OData.Core.V1.DefaultNamespace');
        $defaultNamespace->addAttribute('Bool', Boolean::URL_TRUE);

        $odataVersions = $schemaAnnotations->addChild('Annotation');
        $odataVersions->addAttribute('Term', 'Org.OData.Core.V1.ODataVersions');
        $odataVersionsCollection = $odataVersions->addChild('Collection');
        $odataVersionsCollection->addChild('String', '4.01');

        $supportedFormats = $schemaAnnotations->addChild('Annotation');
        $supportedFormats->addAttribute('Term', 'Org.OData.Capabilities.V1.SupportedFormats');
        $supportedFormatsCollection = $supportedFormats->addChild('Collection');

        /** @var \Flat3\OData\Transaction\Metadata $attribute */
        foreach ([
                     Full::class,
                     Minimal::class,
                     None::class,
                 ] as $attribute
        ) {
            $supportedFormatsCollection->addChild(
                'String',
                'application/json;'.(new ParameterList())
                    ->addParameter('odata.metadata', $attribute::name)
                    ->addParameter('IEEE754Compatible', Boolean::URL_TRUE)
                    ->addParameter('odata.streaming', Boolean::URL_TRUE)
            );
        }

        $transaction->outputRaw($root->asXML());
    }

    public function response(Transaction $transaction): StreamedResponse
    {
        $transaction->setContentTypeXml();

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emit($transaction);
        });
    }
}
