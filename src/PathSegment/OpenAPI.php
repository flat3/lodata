<?php

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Core\V1\Description;
use Flat3\Lodata\Annotation\Core\V1\LongDescription;
use Flat3\Lodata\Annotation\Core\V1\SchemaVersion;
use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EntitySet\CountInterface;
use Flat3\Lodata\Interfaces\EntitySet\CreateInterface;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\ExpandInterface;
use Flat3\Lodata\Interfaces\EntitySet\FilterInterface;
use Flat3\Lodata\Interfaces\EntitySet\OrderByInterface;
use Flat3\Lodata\Interfaces\EntitySet\PaginationInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Interfaces\EntitySet\SearchInterface;
use Flat3\Lodata\Interfaces\EntitySet\TokenPaginationInterface;
use Flat3\Lodata\Interfaces\EntitySet\UpdateInterface;
use Flat3\Lodata\Interfaces\JsonInterface;
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResponseInterface;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\Operation;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Property;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Transaction\MediaType;
use Flat3\Lodata\Transaction\Option\Count;
use Flat3\Lodata\Transaction\Option\Filter;
use Flat3\Lodata\Transaction\Option\Search;
use Flat3\Lodata\Transaction\Option\Skip;
use Flat3\Lodata\Transaction\Option\Top;
use Flat3\Lodata\Type\Binary;
use Flat3\Lodata\Type\Boolean;
use Flat3\Lodata\Type\Byte;
use Flat3\Lodata\Type\Date;
use Flat3\Lodata\Type\DateTimeOffset;
use Flat3\Lodata\Type\Decimal;
use Flat3\Lodata\Type\Double;
use Flat3\Lodata\Type\Duration;
use Flat3\Lodata\Type\Guid;
use Flat3\Lodata\Type\Int16;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\Int64;
use Flat3\Lodata\Type\SByte;
use Flat3\Lodata\Type\Single;
use Flat3\Lodata\Type\Stream;
use Flat3\Lodata\Type\String_;
use Flat3\Lodata\Type\TimeOfDay;
use Illuminate\Http\Request;
use stdClass;

class OpenAPI implements PipeInterface, ResponseInterface, JsonInterface
{
    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        if ($currentSegment !== 'openapi.json') {
            throw new PathNotHandledException();
        }

        if ($argument || $nextSegment) {
            throw new BadRequestException('openapi_argument', 'openapi.json must be the only argument in the path');
        }

        $transaction->assertMethod(Request::METHOD_GET);

        return new self();
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $transaction->sendContentType(MediaType::factory()->parse(MediaType::json));

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emitJson($transaction);
        });
    }

    public function emitJson(Transaction $transaction): void
    {
        $oas = new stdClass();
        $oas->openapi = '3.0.1';

        $info = new stdClass();
        $oas->info = $info;

        $endpoint = rtrim(Lodata::getEndpoint(), '/');

        $description = Description::getModelAnnotation();
        $info->title = $description
            ? $description->toJson()
            : __('OData Service for namespace :namespace', ['namespace' => Lodata::getNamespace()]);

        $schemaVersion = SchemaVersion::getModelAnnotation();
        $info->version = $schemaVersion ? $schemaVersion->toJson() : '1.0.0';

        $longDescription = LongDescription::getModelAnnotation();
        $standardDescription = __(<<<'DESC'
This OData service is located at [:endpoint](:endpoint)

## References
- :refCore
- :refMeasures
DESC, [
            'endpoint' => $endpoint,
            'refCore' => '[Org.OData.Core.V1](https://github.com/oasis-tcs/odata-vocabularies/blob/master/vocabularies/Org.OData.Core.V1.md)',
            'refMeasures' => '[Org.OData.Measures.V1](https://github.com/oasis-tcs/odata-vocabularies/blob/master/vocabularies/Org.OData.Measures.V1.md)',
        ]);

        $info->description = $longDescription ? $longDescription->toJson() : $standardDescription;

        $oas->servers = [
            [
                'url' => $endpoint,
            ]
        ];

        $tags = [];

        /** @var EntitySet|Singleton $resource */
        foreach (Lodata::getResources()->sliceByClass([Singleton::class, EntitySet::class]) as $resource) {
            $tag = [
                'name' => $resource->getName(),
            ];

            $description = $resource->getAnnotations()->sliceByClass(Description::class)->first();
            if ($description) {
                $tag['description'] = $description->toJson();
            }

            $tags[] = $tag;
        }

        $oas->tags = $tags;

        $paths = new stdClass();
        $oas->paths = $paths;

        /**
         * 4.5.1
         * @var EntitySet $entitySet
         */
        foreach (Lodata::getResources()->sliceByClass(EntitySet::class) as $entitySet) {
            $pathItemObject = new stdClass();
            $paths->{'/'.$entitySet->getName()} = $pathItemObject;

            // 4.5.1.1 Query a Collection of Entities
            if ($entitySet instanceof QueryInterface) {
                $queryObject = new stdClass();
                $pathItemObject->{'get'} = $queryObject;
                $queryObject->summary = __('Get entities from :name', ['name' => $entitySet->getName()]);
                $queryObject->tags = [$entitySet->getName()];

                $parameters = [];

                if ($entitySet instanceof CountInterface) {
                    $parameters[] = ['$ref' => '#/components/parameters/count'];
                }

                if ($entitySet instanceof ExpandInterface) {
                    $parameters[] = $this->getExpandParameterObject($entitySet);
                }

                $parameters[] = $this->getSelectParameterObject($entitySet);

                if ($entitySet instanceof FilterInterface) {
                    $parameters[] = ['$ref' => '#/components/parameters/filter'];
                }

                if ($entitySet instanceof SearchInterface) {
                    $parameters[] = ['$ref' => '#/components/parameters/search'];
                }

                if ($entitySet instanceof PaginationInterface) {
                    $parameters[] = ['$ref' => '#/components/parameters/top'];
                    if ($entitySet instanceof TokenPaginationInterface) {
                        $parameters[] = ['$ref' => '#/components/parameters/skiptoken'];
                    } else {
                        $parameters[] = ['$ref' => '#/components/parameters/skip'];
                    }
                }

                if ($entitySet instanceof OrderByInterface) {
                    $orderable = array_merge(
                        ...array_values(
                            $entitySet->getType()
                                ->getDeclaredProperties()
                                ->filter(function (DeclaredProperty $property) {
                                    return $property->isFilterable();
                                })->map(function (DeclaredProperty $property) {
                                    return [$property->getName(), $property->getName().' desc'];
                                })
                        )
                    );

                    $parameters[] = [
                        'name' => '$orderby',
                        'in' => 'query',
                        'description' => __(
                            'Order items by property values, see :ref',
                            ['ref' => '[OData Sorting](https://docs.oasis-open.org/odata/odata/v4.01/cs01/part1-protocol/odata-v4.01-cs01-part1-protocol.html#sec_SystemQueryOptionorderby)']
                        ),
                        'explode' => false,
                        'schema' => [
                            'type' => Constants::OAPI_ARRAY,
                            'uniqueItems' => true,
                            'items' => [
                                'type' => Constants::OAPI_STRING,
                                'enum' => $orderable,
                            ]
                        ]
                    ];
                }

                $queryObject->parameters = $parameters;
                $properties = [
                    'value' => [
                        'type' => Constants::OAPI_ARRAY,
                        'items' => [
                            '$ref' => '#/components/schemas/'.$entitySet->getType()->getIdentifier(),
                        ]
                    ]
                ];

                if ($entitySet instanceof CountInterface) {
                    $properties['@count'] = [
                        '$ref' => '#/components/schemas/count',
                    ];
                }

                $queryObject->responses = [
                    Response::HTTP_OK => [
                        'description' => __('Retrieved entities'),
                        'content' => [
                            MediaType::json => [
                                'schema' => [
                                    'type' => Constants::OAPI_OBJECT,
                                    'title' => __('Collection of :name', ['name' => $entitySet->getName()]),
                                    'properties' => $properties,
                                ]
                            ]
                        ]
                    ],
                    Response::HTTP_ERROR_ANY => [
                        '$ref' => '#/components/responses/error',
                    ],
                ];
            }

            // 4.5.1.2 Create an Entity
            if ($entitySet instanceof CreateInterface) {
                $operationObject = new stdClass();
                $pathItemObject->{'post'} = $operationObject;
                $operationObject->summary = __('Add new entity to :name', ['name' => $entitySet->getName()]);
                $operationObject->tags = [$entitySet->getName()];

                $requestBody = [
                    'required' => true,
                    'description' => __('New entity'),
                    'content' => [
                        MediaType::json => [
                            'schema' => [
                                '$ref' => '#/components/schemas/'.$entitySet->getType()->getIdentifier(),
                            ]
                        ]
                    ]
                ];
                $operationObject->requestBody = $requestBody;

                $responses = [
                    Response::HTTP_CREATED => [
                        'description' => __('Created entity'),
                        'content' => [
                            MediaType::json => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/'.$entitySet->getType()->getIdentifier(),
                                ]
                            ]
                        ]
                    ],
                    Response::HTTP_NO_CONTENT => [
                        'description' => __('Success'),
                    ],
                    Response::HTTP_ERROR_ANY => [
                        '$ref' => '#/components/responses/error',
                    ],
                ];
                $operationObject->responses = $responses;
            }

            $entityType = $entitySet->getType();

            if ($entitySet instanceof ReadInterface || $entitySet instanceof UpdateInterface || $entitySet instanceof DeleteInterface) {
                $pathItemObject = new stdClass();
                $paths->{"/{$entitySet->getName()}/{{$entitySet->getType()->getKey()->getName()}}"} = $pathItemObject;

                $pathItemObject->parameters = [
                    [
                        'description' => __('key: :key', ['key' => $entityType->getKey()->getName()]),
                        'in' => 'path',
                        'name' => $entityType->getKey()->getName(),
                        'required' => true,
                        'schema' => $entityType->getKey()->getType()->toOpenAPISchema(),
                    ],
                ];

                if ($entitySet instanceof ReadInterface) {
                    $queryObject = new stdClass();
                    $pathItemObject->{'get'} = $queryObject;
                    $queryObject->summary = __('Get entity from :set by key', ['set' => $entitySet->getName()]);
                    $queryObject->tags = [$entitySet->getName()];
                    $parameters = [];
                    $parameters[] = $this->getSelectParameterObject($entitySet);
                    if ($entitySet instanceof ExpandInterface) {
                        $parameters[] = $this->getExpandParameterObject($entitySet);
                    }
                    $queryObject->parameters = $parameters;

                    $responses = new stdClass();
                    $responses->{Response::HTTP_OK} = [
                        'description' => __('Retrieved entity'),
                        'content' => [
                            MediaType::json => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/'.$entityType->getIdentifier(),
                                ],
                            ],
                        ],
                    ];
                    $responses->{Response::HTTP_ERROR_ANY} = [
                        '$ref' => '#/components/responses/error',
                    ];
                    $queryObject->responses = $responses;

                    /*
                    foreach ($entitySet->getType()->getNavigationProperties() as $navigationProperty) {
                        $queryObject = new stdClass();
                        $pathItemObject->{'get'} = $queryObject;
                        $paths->{"/{$entitySet->getName()}/{{$entitySet->getType()->getKey()->getName()}}/{$navigationProperty->getName()}"} = $pathItemObject;
                    }
                    */
                }

                if ($entitySet instanceof UpdateInterface) {
                    $queryObject = new stdClass();
                    $pathItemObject->{'patch'} = $queryObject;

                    $queryObject->summary = __('Update entity in :set', ['set' => $entitySet->getName()]);
                    $queryObject->tags = [$entitySet->getName()];

                    $queryObject->requestBody = [
                        'description' => __('New property values'),
                        'required' => true,
                        'content' => [
                            MediaType::json => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/'.$entityType->getIdentifier(),
                                ],
                            ],
                        ],
                    ];

                    $queryObject->responses = [
                        Response::HTTP_OK => [
                            '$ref' => '#/components/schemas/'.$entityType->getIdentifier(),
                        ],
                        Response::HTTP_NO_CONTENT => [
                            'description' => __('Success'),
                        ],
                        Response::HTTP_ERROR_ANY => [
                            '$ref' => '#/components/responses/error',
                        ],
                    ];
                }

                if ($entitySet instanceof DeleteInterface) {
                    $queryObject = new stdClass();
                    $pathItemObject->{'delete'} = $queryObject;

                    $queryObject->summary = __('Delete entity from :set', ['set' => $entitySet->getName()]);
                    $queryObject->tags = [$entitySet->getName()];

                    $queryObject->responses = [
                        Response::HTTP_NO_CONTENT => [
                            'description' => __('Success'),
                        ],
                        Response::HTTP_ERROR_ANY => [
                            '$ref' => '#/components/responses/error',
                        ],
                    ];
                }
            }
        }

        /*
        foreach (Lodata::getResources()->sliceByClass(Singleton::class) as $singleton) {
            $pathItemObject = new stdClass();
            $queryObject = new stdClass();
            $pathItemObject->{'get'} = $queryObject;
            $paths->{'/'.$singleton->getName()} = $pathItemObject;
        }
        */

        /** @var Operation $operation */
        foreach (Lodata::getResources()->sliceByClass(Operation::class) as $operation) {
            $boundParameter = $operation->getBoundParameter();
            $pathItemObject = new stdClass();

            switch (true) {
                case null === $boundParameter:
                    $paths->{'/'.$operation->getName()} = $pathItemObject;
                    break;

                case $boundParameter instanceof EntitySet:
                    $paths->{"/{$boundParameter->getName()}/{$operation->getName()}()"} = $pathItemObject;
                    break;
            }

            $queryObject = new stdClass();
            $pathItemObject->{$operation instanceof FunctionInterface ? 'get' : 'post'} = $queryObject;

            $summary = $operation->getAnnotations()->sliceByClass(Description::class)->first();

            if ($summary) {
                $tag['summary'] = $summary->toJson();
            }

            $tags = [];
            $tags[] = $operation->getName();

            if ($boundParameter) {
                $tags[] = $boundParameter->getName();
            }

            $parameters = [];

            $returnType = $operation->getReturnType();

            if ($returnType instanceof EntitySet) {
                $parameters[] = $this->getExpandParameterObject($returnType);
                $parameters[] = $this->getSelectParameterObject($returnType);
                $tags[] = $returnType->getName();
            }

            foreach ($operation->getArguments() as $argument) {
                $tags[] = $argument->getName();

                $parameters[] = [
                ];
            }

            $queryObject->tags = $tags;
            $queryObject->parameters = $parameters;

            $responses = new stdClass();

            $responses->{Response::HTTP_OK} = [
                'description' => '',
                'content' => [
                    MediaType::json => [
                        'schema' => [
                            '$ref' => '#/components/schemas/'.$returnType->getIdentifier(),
                        ],
                    ],
                ],
            ];

            $responses->default = [
                '$ref' => '#/components/responses/error',
            ];

            $queryObject->responses = $responses;
        }

        $components = new stdClass();
        $oas->components = $components;

        $schemas = new stdClass();
        $components->schemas = $schemas;

        foreach (Lodata::getEntityTypes() as $entityType) {
            $schemas->{$entityType->getIdentifier()} = $entityType->toOpenAPISchema();
        }

        $schemas->{ComplexType::identifier} = ['type' => Constants::OAPI_OBJECT,];
        $schemas->{EntityType::identifier} = ['type' => Constants::OAPI_OBJECT,];
        $schemas->{PrimitiveType::identifier} = [
            'anyOf' => [
                Boolean::openApiSchema,
                String_::openApiSchema,
                ['type' => Constants::OAPI_NUMBER],
            ],
        ];
        $schemas->{Annotation::identifier} = String_::openApiSchema;
        $schemas->{NavigationProperty::identifier} = String_::openApiSchema;
        $schemas->{Property::identifier} = String_::openApiSchema;
        $schemas->{Binary::identifier} = Binary::openApiSchema;
        $schemas->{Byte::identifier} = Byte::openApiSchema;
        $schemas->{Date::identifier} = Date::openApiSchema;
        $schemas->{DateTimeOffset::identifier} = DateTimeOffset::openApiSchema;
        $schemas->{Double::identifier} = Double::openApiSchema;
        $schemas->{Duration::identifier} = Duration::openApiSchema;
        $schemas->{Guid::identifier} = Guid::openApiSchema;
        $schemas->{Int16::identifier} = Int16::openApiSchema;
        $schemas->{Int32::identifier} = Int32::openApiSchema;
        $schemas->{Int64::identifier} = Int64::openApiSchema;
        $schemas->{String_::identifier} = String_::openApiSchema;
        $schemas->{Boolean::identifier} = Boolean::openApiSchema;
        $schemas->{SByte::identifier} = SByte::openApiSchema;
        $schemas->{Single::identifier} = Single::openApiSchema;
        $schemas->{Decimal::identifier} = Decimal::openApiSchema;
        $schemas->{Stream::identifier} = Stream::openApiSchema;
        $schemas->{TimeOfDay::identifier} = TimeOfDay::openApiSchema;

        $schemas->{'odata.error'} = [
            'type' => Constants::OAPI_OBJECT,
            'properties' => [
                'error' => [
                    '$ref' => '#/components/schemas/odata.error.main',
                ]
            ]
        ];

        $schemas->{'odata.error.main'} = [
            'type' => Constants::OAPI_OBJECT,
            'properties' => [
                'code' => ['type' => Constants::OAPI_STRING,],
                'message' => ['type' => Constants::OAPI_STRING,],
                'target' => ['type' => Constants::OAPI_STRING,],
                'details' => [
                    'type' => Constants::OAPI_ARRAY,
                    'items' => [
                        '$ref' => '#/components/schemas/odata.error.detail',
                    ],
                ],
                'innererror' => [
                    'type' => Constants::OAPI_OBJECT,
                    'description' => __('The structure of this object is service-specific'),
                ],
            ]
        ];

        $schemas->{'odata.error.detail'} = [
            'type' => Constants::OAPI_OBJECT,
            'properties' => [
                'code' => ['type' => Constants::OAPI_STRING,],
                'message' => ['type' => Constants::OAPI_STRING,],
                'target' => ['type' => Constants::OAPI_STRING,],
            ],
        ];

        $schemas->{'count'} = [
            'anyOf' => [
                ['type' => Constants::OAPI_NUMBER],
                ['type' => Constants::OAPI_STRING],
            ],
            'description' => __(
                'The number of entities in the collection. Available when using the :ref query option',
                ['ref' => '[$count](https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_SystemQueryOptioncount)']
            ),
        ];

        $responses = new stdClass();
        $components->responses = $responses;
        $responses->error = [
            'description' => __('Error'),
            'content' => [
                MediaType::json => [
                    'schema' => [
                        '$ref' => '#/components/schemas/odata.error',
                    ]
                ],
            ],
        ];

        $parameters = new stdClass();
        $components->parameters = $parameters;
        $parameters->top = [
            'name' => Top::param,
            'schema' => [
                'type' => Constants::OAPI_INTEGER,
            ],
            'in' => 'query',
            'description' => __(
                'Show only the first n items, see :ref',
                ['ref' => '[OData Paging – Top](https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_SystemQueryOptiontop)']
            ),
        ];

        $parameters->skip = [
            'name' => Skip::param,
            'schema' => [
                'type' => Constants::OAPI_INTEGER,
            ],
            'in' => 'query',
            'description' => __(
                'Skip the first n items, see :ref',
                ['ref' => '[OData Paging - Skip](https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_SystemQueryOptionskip)'],
            ),
        ];

        $parameters->count = [
            'name' => Count::param,
            'schema' => [
                'type' => Constants::OAPI_BOOLEAN,
            ],
            'in' => 'query',
            'description' => __(
                'Include count of items, see :ref',
                ['ref' => '[OData Count](https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_SystemQueryOptioncount)'],
            ),
        ];

        $parameters->filter = [
            'name' => Filter::param,
            'schema' => [
                'type' => Constants::OAPI_STRING,
            ],
            'in' => 'query',
            'description' => __(
                'Filter items by property values, see :ref',
                ['ref' => '[OData Filtering](https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_SystemQueryOptionfilter)'],
            ),
        ];

        $parameters->search = [
            'name' => Search::param,
            'schema' => [
                'type' => Constants::OAPI_STRING,
            ],
            'in' => 'query',
            'description' => __(
                'Search items by search phrases, see :ref',
                ['ref' => '[OData Searching](https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_SystemQueryOptionsearch)'],
            ),
        ];

        $transaction->sendJson($oas);
    }

    protected function getExpandParameterObject(EntitySet $entitySet): array
    {
        $navigationProperties = $entitySet->getType()->getNavigationProperties()->keys();

        return [
            'name' => '$expand',
            'in' => 'query',
            'description' => __(
                'Expand related entities, see :ref',
                ['ref' => '[OData Expand](https://docs.oasis-open.org/odata/odata/v4.01/cs01/part1-protocol/odata-v4.01-cs01-part1-protocol.html#sec_SystemQueryOptionexpand)']
            ),
            'explode' => false,
            'schema' => [
                'type' => Constants::OAPI_ARRAY,
                'uniqueItems' => true,
                'items' => [
                    'type' => Constants::OAPI_STRING,
                    'enum' => array_merge(['*'], $navigationProperties),
                ]
            ]
        ];
    }

    protected function getSelectParameterObject(EntitySet $entitySet): array
    {
        $declaredProperties = $entitySet->getType()->getDeclaredProperties()->keys();

        return [
            'name' => '$select',
            'in' => 'query',
            'description' => __(
                'Select properties to be returned, see :ref',
                ['ref' => '[OData Select](https://docs.oasis-open.org/odata/odata/v4.01/cs01/part1-protocol/odata-v4.01-cs01-part1-protocol.html#sec_SystemQueryOptionselect)']
            ),
            'explode' => false,
            'schema' => [
                'type' => Constants::OAPI_ARRAY,
                'uniqueItems' => true,
                'items' => [
                    'type' => Constants::OAPI_STRING,
                    'enum' => array_merge(['*'], $declaredProperties)
                ]
            ]
        ];
    }
}