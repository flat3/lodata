<?php

namespace Flat3\Lodata\Controller;

use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\MethodNotAllowedException;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Exception\Protocol\NotAcceptableException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Exception\Protocol\PreconditionFailedException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\EmitInterface;
use Flat3\Lodata\Interfaces\Operation\ArgumentInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\RequestInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\PathSegment;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\ServiceProvider;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Transaction\IEEE754Compatible;
use Flat3\Lodata\Transaction\MediaType;
use Flat3\Lodata\Transaction\Metadata;
use Flat3\Lodata\Transaction\NavigationRequest;
use Flat3\Lodata\Transaction\Option\Count;
use Flat3\Lodata\Transaction\Option\Expand;
use Flat3\Lodata\Transaction\Option\Filter;
use Flat3\Lodata\Transaction\Option\Format;
use Flat3\Lodata\Transaction\Option\OrderBy;
use Flat3\Lodata\Transaction\Option\SchemaVersion;
use Flat3\Lodata\Transaction\Option\Search;
use Flat3\Lodata\Transaction\Option\Select;
use Flat3\Lodata\Transaction\Option\Skip;
use Flat3\Lodata\Transaction\Option\Top;
use Flat3\Lodata\Transaction\Parameter;
use Flat3\Lodata\Transaction\ParameterList;
use Flat3\Lodata\Transaction\Version;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JsonException;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Transaction
 * @package Flat3\Lodata
 */
class Transaction implements ArgumentInterface
{
    /**
     * Transaction ID
     * @var UuidInterface $id
     * @internal
     */
    protected $id;

    /**
     * Request object
     * @var Request $request
     * @internal
     */
    private $request;

    /**
     * Response object
     * @var Response $response
     * @internal
     */
    private $response;

    /**
     * Version object
     * @var Version $version
     * @internal
     */
    private $version;

    /**
     * Prefer header
     * @var ParameterList $preferences
     * @internal
     */
    private $preferences;

    /**
     * Metadata type
     * @var Metadata $metadata
     * @internal
     */
    private $metadata;

    /**
     * IEEE 754 requirement
     * @var IEEE754Compatible $ieee754compatible
     * @internal
     */
    private $ieee754compatible;

    /**
     * Count system query option
     * @var Count $count
     * @internal
     */
    private $count;

    /**
     * Expand system query option
     * @var Expand $expand
     * @internal
     */
    private $expand;

    /**
     * Filter system query optioon
     * @var Filter $filter
     * @internal
     */
    private $filter;

    /**
     * OrderBy system query option
     * @var OrderBy $orderby
     * @internal
     */
    private $orderby;

    /**
     * Search system query option
     * @var Search $search
     * @internal
     */
    private $search;

    /**
     * Select system query option
     * @var Select $select
     * @internal
     */
    private $select;

    /**
     * Requested response format
     * @var Format $format
     * @internal
     */
    private $format;

    /**
     * Skip system query option
     * @var Skip $skip
     * @internal
     */
    private $skip;

    /**
     * Top system query option
     * @var Top $top
     * @internal
     */
    private $top;

    /**
     * Schema version system query option
     * @var SchemaVersion $schemaVersion
     * @internal
     */
    private $schemaVersion;

    /**
     * List of path segment handlers
     * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part2-url-conventions/odata-v4.01-os-part2-url-conventions.html#sec_KeyasSegmentConvention
     * @var PipeInterface[] $handlers
     */
    protected $handlers = [
        EntitySet::class,
        PathSegment\Batch::class,
        PathSegment\Metadata::class,
        PathSegment\Value::class,
        PathSegment\Count::class,
        PathSegment\Filter::class,
        PathSegment\Reference::class,
        Operation::class,
        Singleton::class,
        PropertyValue::class,
    ];

    public function __construct()
    {
        $this->id = Str::uuid();
        $this->count = new Count();
        $this->format = new Format();
        $this->expand = new Expand();
        $this->filter = new Filter();
        $this->orderby = new OrderBy();
        $this->schemaVersion = new SchemaVersion();
        $this->search = new Search();
        $this->select = new Select();
        $this->skip = new Skip();
        $this->top = new Top();
    }

    /**
     * Initialize this transaction using the provided request object
     * @param  RequestInterface  $request  Request
     * @return $this
     */
    public function initialize(RequestInterface $request): self
    {
        $this->setRequest($request);
        $this->response = new Response();

        $this->version = new Version(
            $this->getRequestHeader(Version::versionHeader),
            $this->getRequestHeader(Version::maxVersionHeader)
        );

        $this->preferences = new ParameterList();
        $this->preferences->parse($this->getRequestHeader('prefer'));

        foreach ($this->request->query->keys() as $param) {
            if (Str::startsWith($param, '$') && !in_array($param, $this->getSystemQueryOptions())) {
                throw new BadRequestException(
                    'invalid_system_query_option',
                    sprintf('The provided system query option "%s" is not valid', $param)
                );
            }
        }

        foreach (['compute', 'apply'] as $sqo) {
            if ($this->getSystemQueryOption($sqo)) {
                throw new NotImplementedException(
                    $sqo.'_not_implemented',
                    "The \${$sqo} system query option is not implemented"
                );
            }
        }

        if ($this->getRequestHeader('isolation') || $this->getRequestHeader('odata-isolation')) {
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

        $this->count = Count::factory($this);
        $this->format = Format::factory($this);
        $this->expand = Expand::factory($this);
        $this->filter = Filter::factory($this);
        $this->orderby = OrderBy::factory($this);
        $this->schemaVersion = SchemaVersion::factory($this);
        $this->search = Search::factory($this);
        $this->select = Select::factory($this);
        $this->skip = Skip::factory($this);
        $this->top = Top::factory($this);

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
     * @return Request Request
     */
    public function getRequest(): Request
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
     * Get the $top system query option
     * @return Top Top
     */
    public function getTop(): Top
    {
        return $this->top;
    }

    /**
     * Mark as requested preference as having been applied to the response object
     * @param  string  $key  Preference
     * @param  string  $value  Value
     * @return $this
     */
    public function preferenceApplied(string $key, string $value): self
    {
        $this->response->headers->set('preference-applied', sprintf('%s=%s', $key, $value));
        $this->response->headers->set('vary', 'prefer', true);

        return $this;
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
        return $this->getRequestHeader('accept-charset') ?: MediaType::factory()->parse($this->getResponseHeader('content-type'))->getParameter('charset');
    }

    /**
     * Get the content type provided by the client
     * @return MediaType
     */
    public function getProvidedContentType(): MediaType
    {
        $contentType = $this->getRequestHeader('content-type');

        if (!$contentType) {
            return MediaType::factory()->parse('*/*');
        }

        return MediaType::factory()->parse($contentType);
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
     * Get the negotiated metadata type
     * @return Metadata|null Metadata
     */
    public function getMetadata(): ?Metadata
    {
        return $this->metadata;
    }

    /**
     * Get the content type requested by the client
     * @return MediaType
     */
    public function getAcceptedContentType(): MediaType
    {
        $formatQueryOption = $this->getFormat()->getValue();

        if (Str::startsWith($formatQueryOption, ['json', 'xml'])) {
            if (!in_array($formatQueryOption, ['json', 'xml'])) {
                throw new BadRequestException(
                    'invalid_short_format',
                    'When using a short $format option, parameters cannot be used'
                );
            }

            return MediaType::factory()->parse('application/'.$formatQueryOption);
        }

        if ($formatQueryOption) {
            return MediaType::factory()->parse($formatQueryOption);
        }

        $acceptHeader = $this->getRequestHeader('accept');

        if ($acceptHeader) {
            return MediaType::factory()->parse($acceptHeader);
        }

        return MediaType::factory()->parse('*/*');
    }

    /**
     * Set the content encoding response header
     * @param  string  $encoding  Encoding
     * @return $this
     */
    public function setContentEncoding(string $encoding): self
    {
        $this->sendHeader('content-encoding', $encoding);

        return $this;
    }

    /**
     * Set the content language response header
     * @param  string  $language  Language
     * @return $this
     */
    public function setContentLanguage(string $language): self
    {
        $this->sendHeader('content-language', $language);

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
        $this->sendHeader('content-type', $contentType->toString());

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
        return array_map('rawurldecode', array_filter(explode('/', $this->getPath())));
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
                '%'.str_pad(dechex(ord($unreservedChar)), 2, '0', STR_PAD_LEFT),
                $unreservedChar,
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
        $route = ServiceProvider::route();
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
            throw new BadRequestException('reference_value_missing',
                sprintf('The requested reference value %s did not exist', $key));
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
                $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new BadRequestException('invalid_json', 'Invalid JSON was provided');
            }
        }

        return $content;
    }

    /**
     * Ensure the request method is one of the provided list or throw an exception
     * @param  string|array  $permitted  List of permitted methods
     * @param  string|null  $message  Error message
     * @param  string|null  $code  Error code
     * @throws MethodNotAllowedException
     */
    public function ensureMethod($permitted, ?string $message = null, ?string $code = null): void
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
    public function ensureContentTypeJson(): void
    {
        $subtype = $this->getProvidedContentType()->getSubtype();

        if (!$subtype) {
            return;
        }

        if ($subtype === '*') {
            return;
        }

        if ($subtype === 'json') {
            return;
        }

        throw new NotAcceptableException(
            'not_json',
            'Content provided to this request must be supplied with a JSON content type'
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
            'apply', 'count', 'compute', 'expand', 'format', 'filter', 'orderby', 'search', 'select', 'skip', 'top',
            'schemaversion'
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
     */
    public function getContextUrl(): string
    {
        return ServiceProvider::endpoint().'$metadata';
    }

    /**
     * Get the service document resource URL
     * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_ServiceDocument
     * @return string Resource URL
     */
    public static function getResourceUrl(): string
    {
        return ServiceProvider::endpoint();
    }

    /**
     * Get request properties to expose in the context URL
     * @return array Properties
     */
    public function getContextUrlProperties(): array
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
                $navigationProperties = [];

                if ($navigationTransaction->getSelect()->hasValue()) {
                    foreach ($navigationTransaction->getSelect()->getCommaSeparatedValues() as $navigationSelect) {
                        $navigationProperties[] = $navigationSelect;
                    }
                }

                $properties[$navigationRequest->path()] = sprintf(
                    "%s(%s)",
                    $navigationRequest->path(),
                    implode(',', $navigationProperties)
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
     * Output a raw text value
     * @param  string  $text  Data
     */
    public function outputRaw(string $text)
    {
        $this->sendOutput($text);
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
            $this->outputJsonValue($value);

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
        $this->sendOutput(json_encode((string) $key, JSON_UNESCAPED_SLASHES).':');
    }

    /**
     * Encode and output a JSON value
     * @param  mixed  $value  Value
     */
    public function outputJsonValue($value)
    {
        if ($value instanceof PropertyValue) {
            $value = $value->getValue();
        }

        if ($value instanceof Primitive) {
            $value = $this->ieee754compatible->isTrue() ? $value->toJsonIeee754() : $value->toJson();
        }

        $this->sendOutput(json_encode($value, JSON_UNESCAPED_SLASHES));
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
        return $this->id;
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
     * @return EmitInterface
     * @throws NotFoundException
     * @throws NoContentException
     */
    public function execute(): EmitInterface
    {
        $pathSegments = $this->getPathSegments();

        /** @var PipeInterface|EmitInterface $result */
        $result = null;

        $lastSegment = Arr::last($pathSegments);

        $requiredType = MediaType::factory()
            ->parse('application/json')
            ->setParameter('odata.streaming', Constants::TRUE)
            ->setParameter('odata.metadata', Metadata\Minimal::name)
            ->setParameter('IEEE754Compatible', Constants::FALSE);

        if ($this->getPreferenceValue(Constants::OMIT_VALUES) === Constants::NULLS) {
            $this->preferenceApplied(Constants::OMIT_VALUES, Constants::NULLS);
        }

        $acceptedContentType = $this->getAcceptedContentType();

        switch ($lastSegment) {
            case '$batch':
                $requiredType = MediaType::factory()->parse($acceptedContentType->getOriginal());
                break;

            case '$metadata':
                $requiredType = MediaType::factory()->parse('application/xml');
                break;

            case '$value':
                $requiredType = $acceptedContentType ?: MediaType::factory()->parse('text/plain');
                break;

            case '$count':
                $requiredType = MediaType::factory()->parse('text/plain');
                break;
        }

        $requiredType->setParameter('charset', 'utf-8');
        $contentType = $requiredType->negotiate($this->getAcceptedContentType()->getOriginal());

        $this->metadata = Metadata::factory($contentType->getParameter('odata.metadata'), $this->version);
        $this->ieee754compatible = new IEEE754Compatible($contentType->getParameter('IEEE754Compatible'));

        $this->sendContentType($contentType);
        $this->sendHeader(Version::versionHeader, $this->getVersion());
        $this->response->setStatusCode(Response::HTTP_OK);

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
            throw NoContentException::factory('no_content', 'No content');
        }

        return $result;
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
            $queryParameters = $lexer->maybeMatchingParenthesis();
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
}
