<?php

namespace Flat3\OData\Controller;

use Flat3\OData\Attribute;
use Flat3\OData\DataModel;
use Flat3\OData\Operation;
use Flat3\OData\Operation\Argument;
use Flat3\OData\Property;
use Flat3\OData\Property\Navigation;
use Flat3\OData\Store;
use Flat3\OData\Transaction;
use Flat3\OData\Type\Boolean;
use Flat3\OData\Type\EntityType;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SimpleXMLElement;

class Metadata extends Controller
{
    public function get(Request $request, DataModel $dataModel, Transaction $transaction)
    {
        $transaction->setRequest($request);
        $response = $transaction->getResponse();
        $transaction->setContentTypeXml();

        // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_CSDLXMLDocument
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><edmx:Edmx xmlns:edmx="http://docs.oasis-open.org/odata/ns/edmx" />');
        $version = $transaction->getVersion();
        $root->addAttribute('Version', $version);

        $dataServices = $root->addChild('DataServices');

        // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Schema
        $schema = $dataServices->addChild('Schema', null, 'http://docs.oasis-open.org/odata/ns/edm');
        $schema->addAttribute('Namespace', $dataModel->getNamespace());

        // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EntityContainer
        $entityContainer = $schema->addChild('EntityContainer');
        $entityContainer->addAttribute('Name', 'DefaultContainer');

        // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EntityType
        /** @var EntityType $entityType */
        foreach ($dataModel->getEntityTypes() as $entityType) {
            $entityTypeElement = $schema->addChild('EntityType');
            $entityTypeElement->addAttribute('Name', $entityType->getIdentifier());

            // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Key
            $keyField = $entityType->getKey();

            if ($keyField) {
                $entityTypeKey = $entityTypeElement->addChild('Key');
                $entityTypeKeyPropertyRef = $entityTypeKey->addChild('PropertyRef');
                $entityTypeKeyPropertyRef->addAttribute('Name', $keyField->getIdentifier());
            }

            // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_StructuralProperty
            /** @var Property $property */
            foreach ($entityType->getDeclaredProperties() as $property) {
                $entityTypeProperty = $entityTypeElement->addChild('Property');
                $entityTypeProperty->addAttribute('Name', $property->getIdentifier());

                // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Type
                $entityTypeProperty->addAttribute('Type', $property->getType()->getEdmTypeName());

                // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_TypeFacets
                $entityTypeProperty->addAttribute(
                    'Nullable',
                    Boolean::factory($property->isNullable())->toUrl()
                );
            }

            // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530365
            /** @var Navigation $navigationProperty */
            foreach ($entityType->getNavigationProperties() as $navigationProperty) {
                $targetEntityType = $navigationProperty->getType();

                $navigationPropertyElement = $entityTypeElement->addChild('NavigationProperty');
                $navigationPropertyElement->addAttribute('Name', $navigationProperty->getIdentifier());
                $navigationPropertyType = $dataModel->getNamespace().'.'.$targetEntityType->getIdentifier();
                if ($navigationProperty->isCollection()) {
                    $navigationPropertyType = 'Collection('.$navigationPropertyType.')';
                }

                $navigationPropertyPartner = $navigationProperty->getPartner();
                if ($navigationPropertyPartner) {
                    $navigationPropertyElement->addAttribute(
                        'Partner',
                        $navigationPropertyPartner->getIdentifier()
                    );
                }

                $navigationPropertyElement->addAttribute('Type', $navigationPropertyType);
                $navigationPropertyElement->addAttribute(
                    'Nullable',
                    Boolean::factory($navigationProperty->isNullable())->toUrl()
                );

                /** @var Property\Constraint $constraint */
                foreach ($navigationProperty->getConstraints() as $constraint) {
                    $referentialConstraint = $navigationPropertyElement->addChild('ReferentialConstraint');
                    $referentialConstraint->addAttribute('Property', $constraint->getProperty()->getIdentifier());
                    $referentialConstraint->addAttribute(
                        'ReferencedProperty',
                        $constraint->getReferencedProperty()->getIdentifier()
                    );
                }
            }
        }

        foreach ($dataModel->getResources() as $resource) {
            switch (true) {
                case $resource instanceof Store:
                    // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EntitySet
                    $entitySetElement = $entityContainer->addChild('EntitySet');
                    $entitySetElement->addAttribute('Name', $resource->getIdentifier());
                    $entitySetElement->addAttribute(
                        'EntityType',
                        $dataModel->getNamespace().'.'.$resource->getEntityType()->getIdentifier()
                    );

                    // http://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_NavigationPropertyBinding
                    /** @var Navigation\Binding $binding */
                    foreach ($resource->getNavigationBindings() as $binding) {
                        $navigationPropertyBindingElement = $entitySetElement->addChild('NavigationPropertyBinding');
                        $navigationPropertyBindingElement->addAttribute(
                            'Path',
                            $binding->getPath()->getIdentifier()
                        );
                        $navigationPropertyBindingElement->addAttribute(
                            'Target',
                            $binding->getTarget()->getIdentifier()
                        );
                    }
                    break;

                /** @var Operation $resource */
                case $resource instanceof Operation:
                    $operationElement = $schema->addChild($resource::EDM_TYPE);
                    $operationElement->addAttribute('Name', $resource->getIdentifier());

                    $returnTypeElement = $operationElement->addChild('ReturnType');
                    $returnType = $resource->getReturnType();
                    $returnTypeElement->addAttribute('Type', $returnType->getEdmTypeName());
                    $returnTypeElement->addAttribute(
                        'Nullable',
                        Boolean::factory($resource->isNullable())->toUrl()
                    );

                    /** @var Argument $argument */
                    foreach ($resource->getArguments() as $argument) {
                        $parameterElement = $operationElement->addChild('Parameter');
                        $parameterElement->addAttribute('Name', $argument->getIdentifier());
                        $parameterElement->addAttribute('Type', $argument->getType()->getEdmTypeName());
                        $parameterElement->addAttribute(
                            'Nullable',
                            Boolean::factory($argument->isNullable())->toUrl()
                        );
                    }

                    $operationImport = $entityContainer->addChild($resource::EDM_TYPE.'Import');
                    $operationImport->addAttribute('Name', $resource->getIdentifier());
                    $operationImport->addAttribute(
                        $resource::EDM_TYPE,
                        $dataModel->getNamespace().'.'.$resource->getIdentifier()
                    );
                    break;
            }
        }

        $schemaAnnotations = $schema->addChild('Annotations');
        $schemaAnnotations->addAttribute('Target', $dataModel->getNamespace().'.'.'DefaultContainer');

        $conventionalIds = $schemaAnnotations->addChild('Annotation');
        $conventionalIds->addAttribute('Term', 'Org.OData.Core.V1.ConventionalIDs');
        $conventionalIds->addAttribute('Bool', Boolean::URL_TRUE);

        $dereferencerableIds = $schemaAnnotations->addChild('Annotation');
        $dereferencerableIds->addAttribute('Term', 'Org.OData.Core.V1.DereferenceableIDs');
        $dereferencerableIds->addAttribute('Bool', Boolean::URL_TRUE);

        $conformanceLevel = $schemaAnnotations->addChild('Annotation');
        $conformanceLevel->addAttribute('Term', 'Org.OData.Capabilities.V1.ConformanceLevel');
        $conformanceLevelType = $conformanceLevel->addChild(
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

        /** @var Attribute\Metadata $attribute */
        foreach ([
                     Attribute\Metadata\Full::class,
                     Attribute\Metadata\Minimal::class,
                     Attribute\Metadata\None::class,
                 ] as $attribute
        ) {
            $supportedFormatsCollection->addChild(
                'String',
                'application/json;'.(new Attribute\ParameterList())
                    ->addParameter('odata.metadata', $attribute::name)
                    ->addParameter('IEEE754Compatible', Boolean::URL_TRUE)
                    ->addParameter('odata.streaming', Boolean::URL_TRUE)
            );
        }

        $xml = $root->asXML();

        $response->setCallback(function () use ($xml) {
            echo $xml;
        });

        return $response;
    }
}
