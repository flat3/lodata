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
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\ResponseInterface;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\Operation;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Property;
use Flat3\Lodata\ServiceProvider;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Transaction\MediaType;
use Flat3\Lodata\Transaction\Option\Count;
use Flat3\Lodata\Transaction\Option\Filter;
use Flat3\Lodata\Transaction\Option\Search;
use Flat3\Lodata\Transaction\Option\Skip;
use Flat3\Lodata\Transaction\Option\SkipToken;
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

class OpenAPI implements PipeInterface, ResponseInterface, JsonInterface
{
    const OPENAPI_VERSION = '3.0.3';

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
        $document = (object) [];

        /**
         * 4.1 Field openapi
         * @link https://docs.oasis-open.org/odata/odata-openapi/v1.0/cn01/odata-openapi-v1.0-cn01.html#sec_Fieldopenapi
         */
        $document->openapi = self::OPENAPI_VERSION;

        /**
         * 4.2 Field info
         * @link https://docs.oasis-open.org/odata/odata-openapi/v1.0/cn01/odata-openapi-v1.0-cn01.html#sec_Fieldinfo
         */
        $info = (object) [];
        $document->info = $info;

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

        /**
         * 4.3 Field servers
         * @link https://docs.oasis-open.org/odata/odata-openapi/v1.0/cn01/odata-openapi-v1.0-cn01.html#sec_Fieldservers
         */
        $document->servers = [
            [
                'url' => $endpoint,
            ]
        ];

        /**
         * 4.4 Field tags
         * @link https://docs.oasis-open.org/odata/odata-openapi/v1.0/cn01/odata-openapi-v1.0-cn01.html#sec_Fieldtags
         */
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

        $document->tags = $tags;

        /**
         * 4.5 Field paths
         * @link https://docs.oasis-open.org/odata/odata-openapi/v1.0/cn01/odata-openapi-v1.0-cn01.html#sec_Fieldpaths
         */
        $paths = (object) [];
        $document->paths = $paths;

        /**
         * 4.5.1 Paths for Collections of Entities
         * @link https://docs.oasis-open.org/odata/odata-openapi/v1.0/cn01/odata-openapi-v1.0-cn01.html#sec_PathsforCollectionsofEntities
         * @var EntitySet $entitySet
         */
        foreach (Lodata::getResources()->sliceByClass(EntitySet::class) as $entitySet) {
            $pathItemObject = (object) [];
            $paths->{"/{$entitySet->getName()}"} = $pathItemObject;

            if ($entitySet instanceof QueryInterface) {
                $this->generateQueryRoutes($pathItemObject, $entitySet);
            }

            if ($entitySet instanceof CreateInterface) {
                $this->generateCreateRoutes($pathItemObject, $entitySet);
            }

            /**
             * 4.5.2 Paths for Single Entities
             * @link https://docs.oasis-open.org/odata/odata-openapi/v1.0/cn01/odata-openapi-v1.0-cn01.html#sec_PathsforSingleEntities
             */
            if ($entitySet instanceof ReadInterface || $entitySet instanceof UpdateInterface || $entitySet instanceof DeleteInterface) {
                $pathItemObject = (object) [];
                $paths->{"/{$entitySet->getName()}/{{$entitySet->getType()->getKey()->getName()}}"} = $pathItemObject;

                $pathItemObject->parameters = [$this->generateKeyParameter($entitySet)];

                if ($entitySet instanceof ReadInterface) {
                    $this->generateReadRoutes($pathItemObject, $entitySet);
                }

                if ($entitySet instanceof UpdateInterface) {
                    $this->generateUpdateRoutes($pathItemObject, $entitySet);
                }

                if ($entitySet instanceof DeleteInterface) {
                    $this->generateDeleteRoutes($pathItemObject, $entitySet);
                }
            }

            foreach ($entitySet->getType()->getNavigationProperties() as $navigationProperty) {
                $navigationSet = $entitySet->getBindingByNavigationProperty($navigationProperty)->getTarget();

                $pathItemObject = (object) [];
                $paths->{"/{$entitySet->getName()}/{{$entitySet->getType()->getKey()->getName()}}/{$navigationProperty->getName()}"} = $pathItemObject;

                $pathItemObject->parameters = [$this->generateKeyParameter($entitySet)];

                if ($entitySet instanceof QueryInterface) {
                    $this->generateQueryRoutes($pathItemObject, $navigationSet, $entitySet);
                }

                if ($entitySet instanceof CreateInterface) {
                    $this->generateCreateRoutes($pathItemObject, $navigationSet, $entitySet);
                }
            }
        }

        /** @var Singleton $singleton */
        foreach (Lodata::getResources()->sliceByClass(Singleton::class) as $singleton) {
            $pathItemObject = (object) [];
            $paths->{'/'.$singleton->getName()} = $pathItemObject;

            $parameters = [];

            $queryObject = (object) [];
            $pathItemObject->{'get'} = $queryObject;

            if ($singleton instanceof DeleteInterface) {
                $this->generateDeleteRoutes($pathItemObject, $singleton);
            }

            if ($singleton instanceof ExpandInterface && $singleton->getType()->getNavigationProperties()->hasEntries()) {
                $parameters[] = $this->getExpandParameterObject($singleton);
            }

            $parameters[] = $this->getSelectParameterObject($singleton);

            if ($singleton instanceof UpdateInterface) {
                $this->generateUpdateRoutes($pathItemObject, $singleton);
            }

            $queryObject->parameters = $parameters;

            $responses = [
                Response::HTTP_OK => [
                    'description' => '',
                    'content' => [
                        MediaType::json => [
                            'schema' => [
                                '$ref' => '#/components/schemas/'.$singleton->getType()->getIdentifier(),
                            ],
                        ],
                    ],
                ],
                Response::HTTP_ERROR_ANY => [
                    '$ref' => '#/components/responses/error',
                ],
            ];

            $queryObject->responses = $responses;
        }

        /**
         * 4.5.3 Paths for Action Imports
         * 4.5.4 Paths for Function Imports
         * @link https://docs.oasis-open.org/odata/odata-openapi/v1.0/cn01/odata-openapi-v1.0-cn01.html#sec_PathsforActionImports
         * @link https://docs.oasis-open.org/odata/odata-openapi/v1.0/cn01/odata-openapi-v1.0-cn01.html#sec_PathsforFunctionImports
         * @var Operation $operation
         */
        foreach (Lodata::getResources()->sliceByClass(Operation::class) as $operation) {
            $boundParameter = $operation->getBoundParameter();
            $pathItemObject = (object) [];

            switch (true) {
                case null === $boundParameter:
                    $paths->{'/'.$operation->getName()} = $pathItemObject;
                    break;

                case $boundParameter instanceof EntitySet:
                    $paths->{"/{$boundParameter->getName()}/{$operation->getName()}()"} = $pathItemObject;
                    break;
            }

            $queryObject = (object) [];
            $pathItemObject->{$operation instanceof FunctionInterface ? 'get' : 'post'} = $queryObject;

            $summary = $operation->getAnnotations()->sliceByClass(Description::class)->first();

            if ($summary) {
                $tag['summary'] = $summary->toJson();
            } else {
                $__args = ['name' => $operation->getName()];

                $tag['summary'] = $operation instanceof FunctionInterface
                    ? __('Invoke function :name', $__args)
                    : __('Invoke action :name', $__args);
            }

            $tags = [];
            $tags[] = __('Service Operations');
            $tags[] = $operation->getName();

            if ($boundParameter) {
                $tags[] = $boundParameter->getName();
            }

            $parameters = [];

            $returnType = $operation->getReturnType();

            foreach ($operation->getExternalArguments() as $argument) {
                $tags[] = $argument->getName();

                $parameters[] = [
                    'required' => $argument->isNullable(),
                    'in' => 'query',
                    'name' => $argument->getName(),
                    'schema' => $argument->getType()->toOpenAPISchema(),
                ];
            }

            $queryObject->tags = $tags;
            $queryObject->parameters = $parameters;

            $responses = [];

            if ($returnType) {
                $responses[Response::HTTP_OK] = [
                    'description' => '',
                    'content' => [
                        MediaType::json => [
                            'schema' => [
                                '$ref' => '#/components/schemas/'.$returnType->getIdentifier(),
                            ],
                        ],
                    ],
                ];
            }

            $responses[Response::HTTP_NO_CONTENT] = [
                'description' => __('Success'),
            ];

            $responses[Response::HTTP_ERROR_ANY] = [
                '$ref' => '#/components/responses/error',
            ];

            $queryObject->responses = $responses;
        }

        /**
         * Batch support
         */
        $pathItemObject = (object) [];
        $paths->{'/$batch'} = $pathItemObject;

        $queryObject = (object) [];
        $pathItemObject->{'post'} = $queryObject;

        $queryObject->summary = __('Send a group of requests');

        $queryObject->description = __(
            'Group multiple requests into a single request payload, see :ref',
            ['ref' => '[Batch Requests](https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_BatchRequests)']
        );

        $queryObject->tags = [__('Batch Requests')];

        $route = ServiceProvider::route();

        $requestBody = [
            'required' => true,
            'description' => __('Batch Request'),
            'content' => [
                MediaType::json => [
                    'schema' => [
                        'type' => Constants::OAPI_STRING,
                    ],
                    'example' => [
                        'requests' => [
                            [
                                'id' => '0',
                                'method' => 'get',
                                'url' => "/{$route}/resource(1)"
                            ],
                            [
                                'id' => '1',
                                'method' => 'patch',
                                'url' => "/{$route}/resource(2)",
                                'headers' => [
                                    'Prefer' => 'return=minimal'
                                ],
                                'body' => '<JSON representation of changes to entity>'
                            ],
                        ],
                    ],
                ],
                (string) MediaType::factory()
                    ->parse(MediaType::multipartMixed)
                    ->setParameter('boundary', 'request-separator') => [
                    'schema' => [
                        'type' => Constants::OAPI_STRING,
                    ],
                    'example' => implode("\n", [
                        '--request-separator',
                        'Content-Type: application/http',
                        'Content-Transfer-Encoding: binary',
                        '',
                        "GET {$route}/resource HTTP/1.1",
                        'Accept: application/json',
                        '',
                        '',
                        '-request-separator--',
                    ])
                ],
            ],
        ];

        $queryObject->requestBody = $requestBody;

        $queryObject->responses = [
            Response::HTTP_OK => [
                'description' => __('Batch response'),
                'content' => [
                    MediaType::json => [
                        'schema' => [
                            'type' => Constants::OAPI_STRING,
                        ],
                        'example' => [
                            'responses' => [
                                [
                                    'id' => '0',
                                    'status' => 200,
                                    'body' => '<JSON representation of the entity with key 1>',
                                ],
                                [
                                    'id' => '1',
                                    'status' => 204
                                ],
                            ]
                        ],
                    ],
                    MediaType::multipartMixed => [
                        'schema' => [
                            'type' => Constants::OAPI_STRING,
                        ],
                        'example' => implode("\n", [
                            '--response-separator',
                            'Content-Type: application/http',
                            '',
                            'HTTP/1.1 200 OK',
                            'Content-Type: application/json',
                            '',
                            '{...}',
                            '--response-separator--'
                        ]),
                    ],
                ],
            ],
            Response::HTTP_ERROR_ANY => [
                '$ref' => '#/components/responses/error',
            ],
        ];

        $components = (object) [];
        $document->components = $components;

        $schemas = (object) [];
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

        $responses = (object) [];
        $components->responses = $responses;

        $responses->error = [
            'description' => __('Error'),
            'content' => [
                MediaType::json => [
                    'schema' => [
                        'type' => Constants::OAPI_OBJECT,
                        'properties' => [
                            'error' => [
                                'type' => Constants::OAPI_OBJECT,
                                'properties' => [
                                    'code' => ['type' => Constants::OAPI_STRING,],
                                    'message' => ['type' => Constants::OAPI_STRING,],
                                    'target' => ['type' => Constants::OAPI_STRING,],
                                    'details' => [
                                        'type' => Constants::OAPI_ARRAY,
                                        'items' => [
                                            'type' => Constants::OAPI_OBJECT,
                                            'properties' => [
                                                'code' => ['type' => Constants::OAPI_STRING,],
                                                'message' => ['type' => Constants::OAPI_STRING,],
                                                'target' => ['type' => Constants::OAPI_STRING,],
                                            ],
                                        ],
                                    ],
                                    'innererror' => [
                                        'type' => Constants::OAPI_OBJECT,
                                        'description' => __('The structure of this object is service-specific'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $parameters = (object) [];
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

        $parameters->skiptoken = [
            'name' => SkipToken::param,
            'schema' => [
                'type' => Constants::OAPI_STRING,
            ],
            'in' => 'query',
            'description' => __(
                'Skip using a skip token, see :ref',
                ['ref' => '[OData Server Driven Paging](https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_ServerDrivenPaging)'],
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

        $transaction->sendJson($document);
    }

    protected function getExpandParameterObject(ResourceInterface $resource): array
    {
        return [
            'name' => 'expand',
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
                    'enum' => $resource->getType()->getNavigationProperties()->keys(),
                ]
            ]
        ];
    }

    protected function getSelectParameterObject(ResourceInterface $resource): array
    {
        return [
            'name' => 'select',
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
                    'enum' => array_merge(['*'], $resource->getType()->getDeclaredProperties()->keys()),
                ]
            ]
        ];
    }

    protected function getOrderbyParameterObject($entitySet): array
    {
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

        return [
            'name' => 'orderby',
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

    protected function generateQueryRoutes(
        object $pathItemObject,
        EntitySet $entitySet,
        ?EntitySet $relatedSet = null
    ): void {
        $queryObject = (object) [];
        $pathItemObject->{'get'} = $queryObject;

        $tags = [
            $entitySet->getName(),
        ];

        if ($relatedSet) {
            $queryObject->summary = __('Get entities from related :name', ['name' => $entitySet->getName()]);
            $tags[] = $relatedSet->getName();
        } else {
            $queryObject->summary = __('Get entities from :name', ['name' => $entitySet->getName()]);
        }

        $queryObject->tags = $tags;

        $parameters = [];

        $parameters[] = $this->getSelectParameterObject($entitySet);

        if ($entitySet instanceof CountInterface) {
            $parameters[] = ['$ref' => '#/components/parameters/count'];
        }

        if ($entitySet instanceof ExpandInterface && $entitySet->getType()->getNavigationProperties()->hasEntries()) {
            $parameters[] = $this->getExpandParameterObject($entitySet);
        }

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

        if (
            $entitySet instanceof OrderByInterface &&
            $entitySet->getType()->getDeclaredProperties()->filter(function (DeclaredProperty $property) {
                return $property->isFilterable();
            })->hasEntries()
        ) {
            $parameters[] = $this->getOrderbyParameterObject($entitySet);
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

    protected function generateCreateRoutes(
        object $pathItemObject,
        EntitySet $entitySet,
        ?EntitySet $relatedSet = null
    ): void {
        $operationObject = (object) [];
        $pathItemObject->{'post'} = $operationObject;

        $tags = [
            $entitySet->getName()
        ];

        if ($relatedSet) {
            $operationObject->summary = __('Add new entity to related :name', ['name' => $entitySet->getName()]);
            $tags[] = $relatedSet->getName();
        } else {
            $operationObject->summary = __('Add new entity to :name', ['name' => $entitySet->getName()]);
        }

        $operationObject->tags = $tags;

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

    protected function generateReadRoutes(object $pathItemObject, ResourceInterface $resource): void
    {
        $entityType = $resource->getType();
        $queryObject = (object) [];
        $pathItemObject->{'get'} = $queryObject;
        $queryObject->summary = __('Get entity from :set by key', ['set' => $resource->getName()]);
        $queryObject->tags = [$resource->getName()];

        $parameters = [];

        $parameters[] = $this->getSelectParameterObject($resource);

        if ($resource instanceof ExpandInterface && $resource->getType()->getNavigationProperties()->hasEntries()) {
            $parameters[] = $this->getExpandParameterObject($resource);
        }

        $queryObject->parameters = $parameters;

        $queryObject->responses = [
            Response::HTTP_OK => [
                'description' => __('Retrieved entity'),
                'content' => [
                    MediaType::json => [
                        'schema' => [
                            '$ref' => '#/components/schemas/'.$entityType->getIdentifier(),
                        ],
                    ],
                ],
            ],
            Response::HTTP_ERROR_ANY => [
                '$ref' => '#/components/responses/error',
            ],
        ];
    }

    protected function generateUpdateRoutes(object $pathItemObject, ResourceInterface $resource): void
    {
        $entityType = $resource->getType();
        $queryObject = (object) [];
        $pathItemObject->{'patch'} = $queryObject;

        $queryObject->summary = __('Update entity in :set', ['set' => $resource->getName()]);
        $queryObject->tags = [$resource->getName()];

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

    protected function generateDeleteRoutes(object $pathItemObject, ResourceInterface $resource): void
    {
        $queryObject = (object) [];
        $pathItemObject->{'delete'} = $queryObject;

        $queryObject->summary = __('Delete entity from :set', ['set' => $resource->getName()]);
        $queryObject->tags = [$resource->getName()];

        $queryObject->responses = [
            Response::HTTP_NO_CONTENT => [
                'description' => __('Success'),
            ],
            Response::HTTP_ERROR_ANY => [
                '$ref' => '#/components/responses/error',
            ],
        ];
    }

    protected function generateKeyParameter(EntitySet $entitySet): array
    {
        $key = $entitySet->getType()->getKey();

        return [
            'description' => __('Key: :key', ['key' => $key->getName()]),
            'in' => 'path',
            'name' => $key->getName(),
            'required' => true,
            'schema' => $key->getType()->toOpenAPISchema(),
        ];
    }
}