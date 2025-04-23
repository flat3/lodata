<?php

declare(strict_types=1);

namespace Flat3\Lodata\Controller;

use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\AcceptedException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Exception\Protocol\MethodNotAllowedException;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Exception\Protocol\NotAcceptableException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Exception\Protocol\NotModifiedException;
use Flat3\Lodata\Exception\Protocol\PreconditionFailedException;
use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\JSON;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\JsonInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\RequestInterface;
use Flat3\Lodata\Interfaces\ResponseInterface;
use Flat3\Lodata\Interfaces\TransactionInterface;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\Endpoint;
use Flat3\Lodata\Operation;
use Flat3\Lodata\PathSegment;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Transaction\IEEE754Compatible;
use Flat3\Lodata\Transaction\MediaType;
use Flat3\Lodata\Transaction\MediaTypes;
use Flat3\Lodata\Transaction\MetadataContainer;
use Flat3\Lodata\Transaction\MetadataType;
use Flat3\Lodata\Transaction\NavigationRequest;
use Flat3\Lodata\Transaction\Option\Compute;
use Flat3\Lodata\Transaction\Option\Count;
use Flat3\Lodata\Transaction\Option\Expand;
use Flat3\Lodata\Transaction\Option\Filter;
use Flat3\Lodata\Transaction\Option\Format;
use Flat3\Lodata\Transaction\Option\Id;
use Flat3\Lodata\Transaction\Option\Index;
use Flat3\Lodata\Transaction\Option\OrderBy;
use Flat3\Lodata\Transaction\Option\SchemaVersion;
use Flat3\Lodata\Transaction\Option\Search;
use Flat3\Lodata\Transaction\Option\Select;
use Flat3\Lodata\Transaction\Option\Skip;
use Flat3\Lodata\Transaction\Option\SkipToken;
use Flat3\Lodata\Transaction\Option\Top;
use Flat3\Lodata\Transaction\Parameter;
use Flat3\Lodata\Transaction\ParameterList;
use Flat3\Lodata\Transaction\Version;
use Flat3\Lodata\Type\Collection;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use JsonException;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Throwable;

/**
 * Transaction
 * @package Flat3\Lodata
 */
class Transaction
{
    /**
     * Transaction ID
     * @var UuidInterface $id
     */
    protected $id;

    /**
     * Request object
     * @var Request|RequestInterface $request
     */
    private $request;

    /**
     * Response object
     * @var Response $response
     */
    private $response;

    /**
     * Version object
     * @var Version $version
     */
    private $version;

    /**
     * Prefer header
     * @var ParameterList $preferences
     */
    private $preferences;

    /**
     * Requested metadata type
     * @var MetadataType $metadataType
     */
    private $metadataType;

    /**
     * IEEE 754 requirement
     * @var IEEE754Compatible $ieee754compatible
     */
    private $ieee754compatible;

    /**
     * Compute system query option
     * @var Compute
     */
    private $compute;

    /**
     * Count system query option
     * @var Count $count
     */
    private $count;

    /**
     * Expand system query option
     * @var Expand $expand
     */
    private $expand;

    /**
     * Filter system query optioon
     * @var Filter $filter
     */
    private $filter;

    /**
     * OrderBy system query option
     * @var OrderBy $orderby
     */
    private $orderby;

    /**
     * Search system query option
     * @var Search $search
     */
    private $search;

    /**
     * Select system query option
     * @var Select $select
     */
    private $select;

    /**
     * Requested response format
     * @var Format $format
     */
    private $format;

    /**
     * Skip system query option
     * @var Skip $skip
     */
    private $skip;

    /**
     * Skip token system query option
     * @var SkipToken $skiptoken
     */
    private $skiptoken;

    /**
     * Top system query option
     * @var Top $top
     */
    private $top;

    /**
     * Id system query option
     * @var Id $idOption
     */
    private $idOption;

    /**
     * Index system query option
     * @var Index $index
     */
    private $index;

    /**
     * Schema version system query option
     * @var SchemaVersion $schemaVersion
     */
    private $schemaVersion;

    /**
     * List of entity sets attached to this transaction
     * @var EntitySet[]|TransactionInterface[] $attachedEntitySets
     */
    private $attachedEntitySets = [];

    /**
     * List of entity sets with pending transactions
     * @var TransactionInterface[] $pendingEntitySets
     */
    private $pendingEntitySets = [];

    /**
     * List of path segment handlers
     * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part2-url-conventions/odata-v4.01-os-part2-url-conventions.html#sec_KeyasSegmentConvention
     * @var PipeInterface[] $handlers
     */
    protected $handlers = [
        Entity::class,
        EntitySet::class,
        PathSegment\Batch::class,
        PathSegment\Metadata::class,
        PathSegment\OpenAPI::class,
        PathSegment\Value::class,
        PathSegment\Count::class,
        PathSegment\All::class,
        PathSegment\Filter::class,
        PathSegment\Query::class,
        PathSegment\Reference::class,
        PathSegment\Each::class,
        Operation::class,
        Singleton::class,
        PropertyValue::class,
        PathSegment\Key::class,
        EntityType::class,
    ];

    public function __construct()
    {
        $this->id = Str::uuid();
        $this->compute = new Compute();
        $this->count = new Count();
        $this->format = new Format();
        $this->expand = new Expand();
        $this->filter = new Filter();
        $this->orderby = new OrderBy();
        $this->schemaVersion = new SchemaVersion();
        $this->search = new Search();
        $this->select = new Select();
        $this->skip = new Skip();
        $this->skiptoken = new SkipToken();
        $this->top = new Top();
        $this->idOption = new Id();
        $this->index = new Index();
    }

    /**
     * Initialize this transaction using the provided request object
     * @param  RequestInterface  $request  Request
     * @return $this
     */
    public function initialize(RequestInterface $request): self
    {
        $this->setRequest($request);
        $this->response = App::make(Response::class);

        $this->version = new Version(
            $this->getRequestHeader(Constants::odataVersion),
            $this->getRequestHeader(Constants::odataMaxVersion)
        );

        $this->preferences = new ParameterList();
        $this->preferences->parse($this->getRequestHeader(Constants::prefer));

        foreach ($this->request->query->keys() as $param) {
            if (Str::startsWith($param, '$') && !in_array($param, $this->getSystemQueryOptions())) {
                throw new BadRequestException(
                    'invalid_system_query_option',
                    sprintf('The provided system query option "%s" is not valid', $param)
                );
            }
        }

        if ($this->getSystemQueryOption('apply')) {
            throw new NotImplementedException(
                'apply_not_implemented',
                'The $apply system query option is not implemented'
            );
        }

        if ($this->hasRequestHeader('isolation') || $this->hasRequestHeader('odata-isolation')) {
            throw new PreconditionFailedException('isolation_not_supported', 'Isolation is not supported');
        }

        if ($this->schemaVersion->hasValue() && $this->schemaVersion->getValue() !== '*') {
            throw new NotFoundException(
                'schema_version_not_found',
                'The requested schema version is not available'
            );
        }

        return $this;
    }

    /**
     * Update this transaction based on the provided request object
     * @param  RequestInterface  $request  Request
     * @return $this
     */
    public function setRequest(RequestInterface $request): self
    {
        $this->request = $request;

        $this->compute = (new Compute)->setTransaction($this);
        $this->count = (new Count)->setTransaction($this);
        $this->format = (new Format)->setTransaction($this);
        $this->expand = (new Expand)->setTransaction($this);
        $this->filter = (new Filter)->setTransaction($this);
        $this->orderby = (new OrderBy)->setTransaction($this);
        $this->schemaVersion = (new SchemaVersion)->setTransaction($this);
        $this->search = (new Search)->setTransaction($this);
        $this->select = (new Select)->setTransaction($this);
        $this->skip = (new Skip)->setTransaction($this);
        $this->skiptoken = (new SkipToken)->setTransaction($this);
        $this->top = (new Top)->setTransaction($this);
        $this->idOption = (new Id)->setTransaction($this);
        $this->index = (new Index)->setTransaction($this);

        return $this;
    }

    /**
     * Get the first request header of the provided key
     * @param  string  $key  Key
     * @return string|null Header
     */
    public function getRequestHeader(string $key): ?string
    {
        return $this->request->headers->get($key);
    }

    /**
     * Return whether the provided request header exists
     * @param  string  $key  Key
     * @return bool
     */
    public function hasRequestHeader(string $key): bool
    {
        return null !== $this->getRequestHeader($key);
    }

    /**
     * Get all request headers of the provided key
     * @param  ?string  $key  Key (or null to get all keys)
     * @return array Headers
     */
    public function getRequestHeaders(?string $key = null): array
    {
        return $this->request->headers->all($key);
    }

    /**
     * Get the first response header of the provided key
     * @param  string  $key  Key
     * @return string|null Header
     */
    public function getResponseHeader(string $key): ?string
    {
        return $this->response->headers->get($key);
    }

    /**
     * Get a system query option
     * @param  string  $key  Key
     * @return string|null Option
     */
    public function getSystemQueryOption(string $key): ?string
    {
        $key = strtolower($key);
        $params = array_change_key_case($this->getQueryParams(), CASE_LOWER);

        return $params[$key] ?? ($params['$'.$key] ?? null);
    }

    /**
     * Get all query parameters
     * @return array Query parameters
     */
    public function getQueryParams(): array
    {
        $query = $this->request->query;
        return $query instanceof ParameterBag ? $query->all() : [];
    }

    /**
     * Get a single query parameter
     * @param  string  $key  Key
     * @return string|null Parameter
     */
    public function getQueryParam(string $key): ?string
    {
        return $this->request->query->get($key);
    }

    /**
     * Get the request object
     * @return RequestInterface|Request Request
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Get the response object
     * @return Response Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Get the negotiated version as a string
     * @return string Version
     */
    public function getVersion(): string
    {
        return $this->version->getVersion();
    }

    /**
     * Get the $compute system query option
     * @return Compute Compute
     */
    public function getCompute(): Compute
    {
        return $this->compute;
    }

    /**
     * Get the $count system query option
     * @return Count Count
     */
    public function getCount(): Count
    {
        return $this->count;
    }

    /**
     * Get the $expand system query option
     * @return Expand Expand
     */
    public function getExpand(): Expand
    {
        return $this->expand;
    }

    /**
     * Get the $filter system query option
     * @return Filter Filter
     */
    public function getFilter(): Filter
    {
        return $this->filter;
    }

    /**
     * Get the $orderby system query option
     * @return OrderBy OrderBy
     */
    public function getOrderBy(): OrderBy
    {
        return $this->orderby;
    }

    /**
     * Get the $search system query option
     * @return Search Search
     */
    public function getSearch(): Search
    {
        return $this->search;
    }

    /**
     * Get the $select system query option
     * @return Select Select
     */
    public function getSelect(): Select
    {
        return $this->select;
    }

    /**
     * Get the $skip system query option
     * @return Skip Skip
     */
    public function getSkip(): Skip
    {
        return $this->skip;
    }

    /**
     * Get the $skiptoken system query option
     * @return SkipToken Skip Token
     */
    public function getSkipToken(): SkipToken
    {
        return $this->skiptoken;
    }

    /**
     * Get the $top system query option
     * @return Top Top
     */
    public function getTop(): Top
    {
        return $this->top;
    }

    /**
     * Get the $id system query option
     * @return Id Id
     */
    public function getIdOption(): Id
    {
        return $this->idOption;
    }

    /**
     * Get the $index system query option
     * @return Index Index
     */
    public function getIndex(): Index
    {
        return $this->index;
    }

    /**
     * Mark as requested preference as having been applied to the response object
     * @param  string  $key  Preference
     * @param  string  $value  Value
     * @return $this
     */
    public function preferenceApplied(string $key, string $value): self
    {
        $this->response->headers->set(Constants::preferenceApplied, sprintf('%s=%s', $key, $value));
        $this->response->headers->set(Constants::vary, Constants::prefer, true);

        return $this;
    }

    /**
     * Get the IEEE 754 compatibility flag
     * @return IEEE754Compatible
     */
    public function getIeee754Compatible(): IEEE754Compatible
    {
        return $this->ieee754compatible;
    }

    /**
     * Return whether the provided preference was requested
     * @param  string  $preference  Preference
     * @return bool
     */
    public function hasPreference(string $preference): bool
    {
        return $this->getPreference($preference) !== null;
    }

    /**
     * Get a requested preference as a Parameter object
     * @param  string  $preference  Preference
     * @return Parameter|null
     */
    public function getPreference(string $preference): ?Parameter
    {
        return $this->preferences->getParameter($preference) ?? $this->preferences->getParameter('odata.'.$preference);
    }

    /**
     * Get the string value of a requested preference
     * @param  string  $preference
     * @return string|null
     */
    public function getPreferenceValue(string $preference): ?string
    {
        $pref = $this->getPreference($preference);

        return $pref instanceof Parameter ? $pref->getValue() : null;
    }

    /**
     * Get the requested charset
     * @return string|null
     */
    public function getCharset(): ?string
    {
        return $this->getRequestHeader('accept-charset') ?: (new MediaType)->parse($this->getResponseHeader(Constants::contentType))->getParameter('charset');
    }

    /**
     * Get the content type provided by the client
     * @return MediaType
     */
    public function getProvidedContentType(): MediaType
    {
        return (new MediaType)->parse($this->getRequestHeader(Constants::contentType) ?? '');
    }

    /**
     * Get the callback preference URL
     * @return string|null Callback URL
     */
    public function getCallbackUrl(): ?string
    {
        $preference = $this->getPreference('callback');

        if (null === $preference) {
            return null;
        }

        return $preference->getParameter('url');
    }

    /**
     * Create a new metadata container
     * @return MetadataContainer|null Metadata
     */
    public function createMetadataContainer(): MetadataContainer
    {
        return new MetadataContainer($this->metadataType);
    }

    /**
     * Get the content type requested by the client
     * @return MediaTypes
     */
    public function getAcceptedContentTypes(): MediaTypes
    {
        $formatQueryOption = $this->getFormat()->getValue();

        if ($formatQueryOption && Str::startsWith($formatQueryOption, ['json', 'xml'])) {
            if (!in_array($formatQueryOption, ['json', 'xml'])) {
                throw new BadRequestException(
                    'invalid_short_format',
                    'When using a short $format option, parameters cannot be used'
                );
            }

            return MediaTypes::factory('application/'.$formatQueryOption);
        }

        if ($formatQueryOption) {
            return MediaTypes::factory($formatQueryOption);
        }

        $acceptHeader = $this->getRequestHeader('accept');

        if ($acceptHeader) {
            return MediaTypes::factory($acceptHeader);
        }

        return MediaTypes::factory(MediaType::any);
    }

    /**
     * Get the content encoding requested by the client
     * @return string Content encoding
     */
    public function getContentEncoding(): string
    {
        return $this->getRequestHeader(Constants::contentEncoding);
    }

    /**
     * Get the content language requested by the client
     * @return string Content language
     */
    public function getContentLanguage(): string
    {
        return $this->getRequestHeader(Constants::contentLanguage);
    }

    /**
     * Set the content encoding response header
     * @param  string  $encoding  Encoding
     * @return $this
     */
    public function setContentEncoding(string $encoding): self
    {
        $this->sendHeader(Constants::contentEncoding, $encoding);

        return $this;
    }

    /**
     * Set the content language response header
     * @param  string  $language  Language
     * @return $this
     */
    public function setContentLanguage(string $language): self
    {
        $this->sendHeader(Constants::contentLanguage, $language);

        return $this;
    }

    /**
     * Get the requested content format
     * @return Format Format
     */
    public function getFormat(): Format
    {
        return $this->format;
    }

    /**
     * Send the negotiated content type response header
     * @param  MediaType  $contentType  Content type
     * @return $this
     */
    public function sendContentType(MediaType $contentType): self
    {
        $this->sendHeader(Constants::contentType, (string) $contentType);

        return $this;
    }

    /**
     * Send a response header
     * @param  string  $key  Header
     * @param  string  $value  Value
     * @return $this
     */
    public function sendHeader(string $key, string $value): self
    {
        $this->response->headers->set($key, $value);

        return $this;
    }

    /**
     * Get the list of URL-decoded path segments in the request
     * @return array Path segments
     */
    public function getPathSegments(): array
    {
        return array_map('rawurldecode', array_filter(explode('/', $this->getPath()), 'strlen'));
    }

    /**
     * Get the request path with normalization decoding
     * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part2-url-conventions/odata-v4.01-os-part2-url-conventions.html#sec_URLSyntax
     * @return string Path
     */
    public function getPath(): string
    {
        $unreservedChars = array_merge(
            range('A', 'Z'),
            range('a', 'z'),
            range(0, 9),
            ['-', '.', '_', '~']
        );

        $path = $this->getRequestPath();

        foreach ($unreservedChars as $unreservedChar) {
            $path = str_replace(
                '%'.str_pad(dechex(ord((string) $unreservedChar)), 2, '0', STR_PAD_LEFT),
                (string) $unreservedChar,
                $path
            );
        }

        return $path;
    }

    /**
     * Get the request path without any REST prefix
     * @return string Path
     */
    public function getRequestPath(): string
    {
        $route = app(Endpoint::class)->route();
        return Str::substr($this->request->path(), strlen($route));
    }

    /**
     * Get a URL parameter alias
     * @param  string  $key  Key
     * @return string|null Parameter
     * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358951
     */
    public function getParameterAlias(string $key): ?string
    {
        $value = $this->getQueryParam('@'.ltrim($key, '@'));

        if (null === $value) {
            throw new BadRequestException(
                'reference_value_missing',
                sprintf('The requested reference (%s) did not exist', $key)
            );
        }

        return $value;
    }

    /**
     * Get implicit parameter aliases
     * @param  string  $key  Key
     * @return string|null Parameter
     */
    public function getImplicitParameterAlias(string $key): ?string
    {
        if (in_array($key, $this->getSystemQueryOptions(false))) {
            return $this->getParameterAlias($key);
        }

        return $this->getQueryParam($key);
    }

    /**
     * Get the request method
     * @return string Method
     */
    public function getMethod(): string
    {
        return $this->request->method();
    }

    /**
     * Get the request body, decoded if JSON is being provided
     * @return string|array Body
     */
    public function getBody()
    {
        $content = $this->request->getContent();

        if ($this->getProvidedContentType()->getSubtype() === 'json') {
            try {
                $content = JSON::decode($content);
            } catch (JsonException $e) {
                throw new BadRequestException('invalid_json', 'Invalid JSON was provided', $e);
            }
        }

        return $content;
    }

    /**
     * Get the request body, ensuring it is an array
     * @return array
     */
    public function getBodyAsArray(): array
    {
        $body = $this->getBody();

        if (!is_array($body)) {
            throw new BadRequestException(
                'invalid_body',
                'The provided request body was not formed as an object'
            );
        }

        return $body;
    }

    /**
     * Ensure the request method is one of the provided list or throw an exception
     * @param  string|array  $permitted  List of permitted methods
     * @param  string|null  $message  Error message
     * @param  string|null  $code  Error code
     * @throws MethodNotAllowedException
     */
    public function assertMethod($permitted, ?string $message = null, ?string $code = null): void
    {
        $permitted = is_array($permitted) ? $permitted : [$permitted];

        if (in_array($this->getMethod(), $permitted)) {
            return;
        }

        $exception = (new MethodNotAllowedException())
            ->message(
                sprintf(
                    'The %s method is not allowed',
                    $this->getMethod()
                )
            );

        if ($permitted) {
            $exception->header('Allow', implode(' ', $permitted));
        }

        if ($message) {
            $exception->message($message);
        }

        if ($code) {
            $exception->code($message);
        }

        throw $exception;
    }

    /**
     * Ensure that the request content type is JSON
     * @throws NotAcceptableException
     */
    public function assertContentTypeJson(): void
    {
        if ($this->getProvidedContentType()->getSubtype() === 'json') {
            return;
        }

        throw new NotAcceptableException(
            'not_json',
            'Content provided to this endpoint must be supplied with a JSON content type'
        );
    }

    /**
     * Get all system query options from the request, optionally returning them with the $ prefix
     * @param  bool  $prefixed  Use $ prefix
     * @return string[] System query options
     */
    private function getSystemQueryOptions(bool $prefixed = true): array
    {
        $options = [
            'apply', 'count', 'compute', 'expand', 'format', 'filter', 'orderby', 'search', 'select', 'skip',
            'skiptoken', 'top', 'schemaversion', 'id', 'index',
        ];

        if ($prefixed) {
            $options = array_map(function ($option) {
                return '$'.$option;
            }, $options);
        }

        return $options;
    }

    /**
     * Get the service document context URL
     * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_ServiceDocument
     * @return string Context URL
     * @throws BindingResolutionException
     */
    public function getContextUrl(): string
    {
        return self::getResourceUrl().'$metadata';
    }

    /**
     * Get the service document resource URL
     * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_ServiceDocument
     * @return string Resource URL
     * @throws BindingResolutionException
     */
    public static function getResourceUrl(): string
    {
        return app(Endpoint::class)->endpoint();
    }

    /**
     * Get request properties to expose in the context URL
     * @return array Properties
     */
    public function getProjectedProperties(): array
    {
        $properties = [];

        $select = $this->getSelect();
        if ($select->hasValue() && !$select->isStar()) {
            foreach ($select->getCommaSeparatedValues() as $value) {
                $properties[$value] = $value;
            }
        }

        $expand = $this->getExpand();
        if ($expand->hasValue()) {
            $navigationRequests = $this->getNavigationRequests();

            /** @var NavigationRequest $navigationRequest */
            foreach ($navigationRequests as $navigationRequest) {
                $navigationTransaction = new Transaction();
                $navigationTransaction->setRequest($navigationRequest);

                $properties[$navigationRequest->path()] = sprintf(
                    '%s(%s)',
                    $navigationRequest->path(),
                    implode(',', $navigationTransaction->getProjectedProperties())
                );
            }
        }

        return array_values($properties);
    }

    /**
     * Get request properties to expose in the resource URL
     * @return array Properties
     */
    public function getResourceUrlProperties(): array
    {
        $properties = [];

        $select = $this->getSelect();
        if ($select->hasValue() && !$select->isStar()) {
            $properties['$select'] = '('.$select->getValue().')';
        }

        $expand = $this->getExpand();
        if ($expand->hasValue()) {
            $properties['$expand'] = $expand->getValue();
        }

        return $properties;
    }

    /**
     * Output the start of a JSON object
     */
    public function outputJsonObjectStart()
    {
        $this->sendOutput('{');
    }

    /**
     * Output the provided string
     * @param  string  $string  Data
     */
    public function sendOutput(string $string): void
    {
        echo $string;
    }

    /**
     * Send the provided data as encoded JSON
     * @param  mixed  $data  Data
     */
    public function sendJson($data): void
    {
        $this->sendOutput(JSON::encode($data));
    }

    /**
     * Output the end of a JSON object
     */
    public function outputJsonObjectEnd()
    {
        $this->sendOutput('}');
    }

    /**
     * Output the start of a JSON array
     */
    public function outputJsonArrayStart()
    {
        $this->sendOutput('[');
    }

    /**
     * Output the end of a JSON array
     */
    public function outputJsonArrayEnd()
    {
        $this->sendOutput(']');
    }

    /**
     * Output the provided associative array as a set of JSON key/values
     * @param  array  $kv  Array
     */
    public function outputJsonKV(array $kv)
    {
        $keys = array_keys($kv);

        while ($keys) {
            $key = array_shift($keys);
            $value = $kv[$key];

            $this->outputJsonKey($key);

            if ($value instanceof JsonInterface) {
                $value->emitJson($this);
            } else {
                $this->sendJson($value);
            }

            if ($keys) {
                $this->outputJsonSeparator();
            }
        }
    }

    /**
     * Output a JSON object key
     * @param  string  $key  Key
     */
    public function outputJsonKey(string $key)
    {
        $this->sendJson($key);
        $this->sendOutput(':');
    }

    /**
     * Output a JSON property separator
     */
    public function outputJsonSeparator()
    {
        $this->sendOutput(',');
    }

    /**
     * Get the transaction ID
     * @return string Transaction ID
     */
    public function getId(): string
    {
        return (string) $this->id;
    }

    /**
     * Replace the query parameters in this transaction with the ones from the provided transaction
     * @param  Transaction  $incomingTransaction  Transaction
     * @return $this
     */
    public function replaceQueryParams(Transaction $incomingTransaction): self
    {
        foreach (['select'] as $param) {
            $this->$param = $incomingTransaction->$param;
        }

        return $this;
    }

    /**
     * Process the request represented by this transaction
     * @return ResponseInterface
     * @throws NotFoundException
     * @throws NoContentException
     */
    public function process(): ResponseInterface
    {
        $pathSegments = $this->getPathSegments();

        /** @var PipeInterface|ResponseInterface $result */
        $result = null;

        $lastSegment = Arr::last($pathSegments);

        if ($this->getPreferenceValue(Constants::omitValues) === Constants::nulls) {
            $this->preferenceApplied(Constants::omitValues, Constants::nulls);
        }

        $acceptedTypes = $this->getAcceptedContentTypes();

        switch ($lastSegment) {
            case '$metadata':
                $providedTypes = MediaTypes::factory(MediaType::xml, MediaType::json);
                break;

            case '$batch':
                $providedTypes = MediaTypes::factory(MediaType::multipartMixed, MediaType::json);
                break;

            case '$count':
                $providedTypes = MediaTypes::factory(MediaType::text);
                break;

            case '$value':
                $providedTypes = MediaTypes::factory(MediaType::text, MediaType::any);
                break;

            default:
                $providedTypes = (new MediaTypes)->add(
                    (new MediaType)
                        ->parse(MediaType::json)
                        ->setParameter(Constants::charset, 'utf-8')
                        ->setParameter(Constants::streaming, Constants::true)
                        ->setParameter(Constants::metadata, MetadataType\Minimal::name)
                        ->setParameter(Constants::ieee754Compatible, Constants::false)
                );
                break;
        }

        $acceptedType = MediaTypes::negotiate($acceptedTypes, $providedTypes);

        if (null === $acceptedType) {
            throw new NotAcceptableException(
                'unsupported_accept',
                'This route does not support the accepted type, unsupported parameters may have been supplied'
            );
        }

        $this->metadataType = MetadataType::factory(
            $acceptedType->getParameter(Constants::metadata),
            $this->version
        );

        if ('text' === $acceptedType->getType() && !$acceptedType->hasParameter(Constants::charset)) {
            $acceptedType->setParameter(Constants::charset, 'utf-8');
        }

        if ('json' === $acceptedType->getSubtype()) {
            $acceptedType->setParameter(
                $this->version->prefixParameter(Constants::metadata),
                $this->metadataType::name
            );
        }

        $this->ieee754compatible = new IEEE754Compatible(
            $acceptedType->getParameter(Constants::ieee754Compatible)
        );

        $this->sendContentType($acceptedType);
        $this->sendHeader(Constants::odataVersion, $this->getVersion());
        $this->response->setStatusCode(Response::HTTP_OK);

        if ($acceptedType->getParameter(Constants::streaming) === Constants::false) {
            $this->response->setStreaming(false);
        }

        if (!$pathSegments) {
            return new PathSegment\Service();
        }

        while ($pathSegments) {
            $currentSegment = array_shift($pathSegments);
            $nextSegment = $pathSegments[0] ?? null;

            foreach ($this->handlers as $handler) {
                try {
                    $result = $handler::pipe($this, $currentSegment, $nextSegment, $result);
                    continue 2;
                } catch (PathNotHandledException $e) {
                    continue;
                }
            }

            throw new NotFoundException('no_handler', 'No route handler was able to process this request');
        }

        if (null === $result) {
            throw new NoContentException('no_content', 'No content');
        }

        return $result;
    }

    /**
     * Execute the transaction
     * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358891
     * @return Response Response
     */
    public function execute(): Response
    {
        try {
            $emitter = $this->process();
            $this->startTransaction();
            $response = $emitter->response($this);
            $this->commit();
            return $response;
        } catch (AcceptedException|NoContentException $e) { // Success responses
            $this->commit();
            throw $e;
        } catch (ProtocolException $e) { // Error responses
            $this->rollback();
            throw $e;
        } catch (Throwable $e) { // Uncaptured errors
            $this->rollback();
            throw new InternalServerErrorException('unknown_error', $e->getMessage(), $e);
        }
    }

    /**
     * Get the navigation requests embedded in this transaction
     * @return ObjectArray Navigation requests
     */
    public function getNavigationRequests(): ObjectArray
    {
        $expanded = $this->getExpand()->getValue();

        $requests = new ObjectArray();

        if (!$expanded) {
            return $requests;
        }

        $lexer = new Lexer($expanded);

        while (!$lexer->finished()) {
            $path = $lexer->identifier();

            $navigationRequest = new NavigationRequest();
            $navigationRequest->setPath($path);
            $queryParameters = $lexer->with(function (Lexer $lexer) {
                return $lexer->matchingParenthesis();
            });
            if ($queryParameters) {
                $navigationRequest->setQueryString($queryParameters);
            }

            $requests[] = $navigationRequest;

            if (!$lexer->finished()) {
                $lexer->char(',');
            }
        }

        return $requests;
    }

    /**
     * Attach an entity set to this transaction
     * @param  EntitySet  $entitySet
     * @return $this
     */
    public function attachEntitySet(EntitySet $entitySet): self
    {
        if (!$entitySet instanceof TransactionInterface) {
            return $this;
        }

        $entitySet->assertTransaction();
        $this->attachedEntitySets[$entitySet->getName()] = $entitySet;

        return $this;
    }

    /**
     * Start transactions on attached entity sets
     */
    public function startTransaction()
    {
        foreach ($this->attachedEntitySets as $entitySet) {
            $this->pendingEntitySets[] = $entitySet;
            $entitySet->startTransaction();
        }
    }

    /**
     * Commit all entity sets attached to this transaction
     */
    public function commit()
    {
        foreach ($this->pendingEntitySets as $entitySet) {
            $entitySet->commit();
        }

        $this->pendingEntitySets = [];
    }

    /**
     * Rollback all entity sets attached to this transaction
     */
    public function rollback()
    {
        foreach ($this->pendingEntitySets as $entitySet) {
            $entitySet->rollback();
        }

        $this->pendingEntitySets = [];
    }

    /**
     * Set the value of the ETag header
     * @param  string  $etag  ETag header
     * @return $this
     */
    public function setETagHeader(string $etag): self
    {
        $this->sendHeader(Constants::etag, $etag);

        return $this;
    }

    /**
     * Validate that the provided ETag matches the current If-Match header
     * @param  string|null  $etag  ETag
     */
    public function assertIfMatchHeader(?string $etag): void
    {
        $ifMatches = $this->getRequestHeaders(Constants::ifMatch);
        $ifNoneMatches = $this->getRequestHeaders(Constants::ifNoneMatch);

        $this->request->headers->remove(Constants::ifMatch);
        $this->request->headers->remove(Constants::ifNoneMatch);

        if ($ifMatches) {
            foreach ($ifMatches as $ifMatch) {
                if ($ifMatch === '*' || $ifMatch === $etag) {
                    return;
                }
            }

            throw new PreconditionFailedException(
                'etag_mismatch',
                'The provided If-Match header did not match the current ETag value'
            );
        }

        if ($ifNoneMatches) {
            foreach ($ifNoneMatches as $ifNoneMatch) {
                if ($ifNoneMatch === '*' || $ifNoneMatch !== $etag) {
                    return;
                }
            }

            if ($this->getMethod() === Request::METHOD_GET) {
                throw new NotModifiedException();
            }

            throw new PreconditionFailedException(
                'etag_mismatch',
                'The provided If-None-Match header matched the current ETag value',
            );
        }
    }

    /**
     * Process deltas in the request body
     * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_DeltaPayloads
     * @link https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_DeltaPayload
     * @param  Entity  $parentEntity
     */
    public function processDeltaPayloads(Entity $parentEntity): void
    {
        $body = $this->getBody();

        if (!$body) {
            return;
        }

        $navigationProperties = $parentEntity->getType()->getNavigationProperties()->pick(array_keys($body));

        /** @var NavigationProperty $navigationProperty */
        foreach ($navigationProperties as $navigationProperty) {
            $deltaPayloads = $body[$navigationProperty->getName()] ?? [];
            $deltaResponseSet = new Collection();
            $deltaResponseSet->setUnderlyingType($navigationProperty->getEntityType());

            foreach ($deltaPayloads as $deltaPayload) {
                $entity = null;
                $binding = $parentEntity->getEntitySet()->getBindingByNavigationProperty($navigationProperty);

                $deltaRequest = new NavigationRequest();
                $deltaRequest->setOuterRequest($this->getRequest());
                $deltaRequest->setContent(JSON::encode($deltaPayload));
                $deltaRequest->setNavigationProperty($navigationProperty);

                $deltaTransaction = clone $this;
                $deltaTransaction->setRequest($deltaRequest);

                if (array_key_exists('@id', $deltaPayload)) {
                    /** @var Entity $entity */
                    try {
                        $entity = EntitySet::pipe($deltaTransaction, $deltaPayload['@id']);
                    } catch (PathNotHandledException|NotFoundException $e) {
                        throw new BadRequestException(
                            'related_entity_missing',
                            'The requested related entity did not exist',
                            $e
                        );
                    }

                    if ($this->getMethod() === Request::METHOD_POST && count($deltaPayload) > 1) {
                        throw new BadRequestException(
                            'related_entity_cannot_update',
                            'Entity create requests cannot contain content for existing related entities',
                        );
                    }
                }

                $entityId = $parentEntity->newPropertyValue();
                $entityId->setProperty($navigationProperty);
                $entityId->setValue($parentEntity->getEntityId());

                $entitySet = clone $binding->getTarget();
                $entitySet->setTransaction($deltaTransaction);
                $entitySet->setNavigationSource($entityId);

                switch (true) {
                    case array_key_exists('@removed', $deltaPayload):
                        $entitySet->processDeltaRemove(
                            $deltaPayload['@removed']['reason'] ?? '', $deltaTransaction, $entity
                        );
                        break;

                    case array_key_exists('@id', $deltaPayload):
                        $entity = $entitySet->processDeltaModify($deltaTransaction, $entity);
                        break;

                    default:
                        $entity = $entitySet->processDeltaCreate($deltaTransaction);
                        break;
                }

                $deltaResponseSet[] = $entity;
            }

            $deltaProperty = $parentEntity->newPropertyValue();
            $deltaProperty->setProperty($navigationProperty);
            $deltaProperty->setValue($deltaResponseSet);
            $parentEntity->addPropertyValue($deltaProperty);
        }
    }
}
