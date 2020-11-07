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
 * Class Transaction
 *
 * @package Flat3\Lodata
 */
class Transaction implements ArgumentInterface
{
    /** @var UuidInterface $id */
    protected $id;

    /** @var Request $request */
    private $request;

    /** @var Response $response */
    private $response;

    /** @var Version $version */
    private $version;

    /** @var ParameterList $preferences */
    private $preferences;

    /** @var Metadata $metadata */
    private $metadata;

    /** @var IEEE754Compatible $ieee754compatible */
    private $ieee754compatible;

    /** @var Count $count */
    private $count;

    /** @var Expand $expand */
    private $expand;

    /** @var Filter $filter */
    private $filter;

    /** @var OrderBy $orderby */
    private $orderby;

    /** @var Search $search */
    private $search;

    /** @var Select $select */
    private $select;

    /** @var Format $format */
    private $format;

    /** @var Skip $skip */
    private $skip;

    /** @var Top $top */
    private $top;

    /** @var SchemaVersion $schemaVersion */
    private $schemaVersion;

    /* Correct handler evaluation order described in https://docs.oasis-open.org/odata/odata/v4.01/os/part2-url-conventions/odata-v4.01-os-part2-url-conventions.html#sec_KeyasSegmentConvention */
    /** @var PipeInterface[] $handlers */
    protected $handlers = [
        EntitySet::class,
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

    public function initialize(Request $request): self
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

    public function getRequestHeader($key): ?string
    {
        return $this->request->headers->get($key);
    }

    public function getRequestHeaders($key): array
    {
        return $this->request->headers->all($key);
    }

    public function getResponseHeader($key): ?string
    {
        return $this->response->headers->get($key);
    }

    public function getSystemQueryOption(string $key): ?string
    {
        $key = strtolower($key);
        $params = array_change_key_case($this->getQueryParams(), CASE_LOWER);

        return $params[$key] ?? ($params['$'.$key] ?? null);
    }

    public function getQueryParams(): array
    {
        $query = $this->request->query;
        return $query instanceof ParameterBag ? $query->all() : [];
    }

    public function getQueryParam(string $key): ?string
    {
        return $this->request->query->get($key);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getVersion(): string
    {
        return $this->version->getVersion();
    }

    /**
     * @return Count
     */
    public function getCount(): Count
    {
        return $this->count;
    }

    /**
     * @return Expand
     */
    public function getExpand(): Expand
    {
        return $this->expand;
    }

    /**
     * @return Filter
     */
    public function getFilter(): Filter
    {
        return $this->filter;
    }

    /**
     * @return OrderBy
     */
    public function getOrderBy(): OrderBy
    {
        return $this->orderby;
    }

    /**
     * @return Search
     */
    public function getSearch(): Search
    {
        return $this->search;
    }

    /**
     * @return Select
     */
    public function getSelect(): Select
    {
        return $this->select;
    }

    /**
     * @return Skip
     */
    public function getSkip(): Skip
    {
        return $this->skip;
    }

    /**
     * @return Top
     */
    public function getTop(): Top
    {
        return $this->top;
    }

    public function preferenceApplied($key, $value): self
    {
        $this->response->headers->set('preference-applied', sprintf('%s=%s', $key, $value));
        $this->response->headers->set('vary', 'prefer', true);

        return $this;
    }

    public function hasPreference(string $preference): bool
    {
        return $this->getPreference($preference) !== null;
    }

    public function getPreference(string $preference): ?Parameter
    {
        return $this->preferences->getParameter($preference) ?? $this->preferences->getParameter('odata.'.$preference);
    }

    public function getPreferenceValue(string $preference): ?string
    {
        $pref = $this->getPreference($preference);

        return $pref instanceof Parameter ? $pref->getValue() : null;
    }

    public function getCharset(): ?string
    {
        return $this->getRequestHeader('accept-charset') ?: MediaType::factory()->parse($this->getResponseHeader('content-type'))->getParameter('charset');
    }

    public function getProvidedContentType(): MediaType
    {
        return MediaType::factory()->parse($this->getRequestHeader('content-type'));
    }

    public function getCallbackUrl(): ?string
    {
        $preference = $this->getPreference('callback');

        if (null === $preference) {
            return null;
        }

        return $preference->getParameter('url');
    }

    public function getMetadata(): ?Metadata
    {
        return $this->metadata;
    }

    public function getAcceptedContentType(): string
    {
        $formatQueryOption = $this->getFormat()->getValue();

        if (Str::startsWith($formatQueryOption, ['json', 'xml'])) {
            if (!in_array($formatQueryOption, ['json', 'xml'])) {
                throw new BadRequestException(
                    'invalid_short_format',
                    'When using a short $format option, parameters cannot be used'
                );
            }

            return 'application/'.$formatQueryOption;
        }

        if ($formatQueryOption) {
            return $formatQueryOption;
        }

        $acceptHeader = $this->getRequestHeader('accept');

        if ($acceptHeader) {
            return $acceptHeader;
        }

        return '*/*';
    }

    public function setContentEncoding($encoding): self
    {
        $this->sendHeader('content-encoding', $encoding);

        return $this;
    }

    public function setContentLanguage($language): self
    {
        $this->sendHeader('content-language', $language);

        return $this;
    }

    /**
     * @return Format
     */
    public function getFormat(): Format
    {
        return $this->format;
    }

    public function sendContentType(MediaType $contentType): self
    {
        $this->sendHeader('content-type', $contentType->toString());

        return $this;
    }

    public function sendHeader(string $key, string $value): self
    {
        $this->response->headers->set($key, $value);

        return $this;
    }

    public function getPathSegments(): array
    {
        return array_map('rawurldecode', array_filter(explode('/', $this->getPath())));
    }

    public function getPath(): string
    {
        // Percent encoding normalization
        // https://docs.oasis-open.org/odata/odata/v4.01/os/part2-url-conventions/odata-v4.01-os-part2-url-conventions.html#sec_URLSyntax
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

    public function getRequestPath(): string
    {
        $route = ServiceProvider::route();
        return Str::substr($this->request->path(), strlen($route));
    }

    public function getParameterAlias(string $key): ?string
    {
        $value = $this->getQueryParam('@'.ltrim($key, '@'));

        if (null === $value) {
            throw new BadRequestException('reference_value_missing',
                sprintf('The requested reference value %s did not exist', $key));
        }

        return $value;
    }

    public function getImplicitParameterAlias(string $key): ?string
    {
        if (in_array($key, $this->getSystemQueryOptions(false))) {
            return $this->getParameterAlias($key);
        }

        return $this->getQueryParam($key);
    }

    public function getMethod(): string
    {
        return $this->request->method();
    }

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

        throw new NotAcceptableException('not_json',
            'Content provided to this request must be supplied with a JSON content type');
    }

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
     *
     * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_ServiceDocument
     *
     * @return string
     */
    public function getContextUrl(): string
    {
        return ServiceProvider::endpoint().'$metadata';
    }

    /**
     * Get the service document URL
     *
     * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_ServiceDocument
     *
     * @return string
     */
    public static function getResourceUrl(): string
    {
        return ServiceProvider::endpoint();
    }

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

    public function outputJsonObjectStart()
    {
        $this->sendOutput('{');
    }

    public function sendOutput(string $string): void
    {
        echo $string;
    }

    public function outputJsonObjectEnd()
    {
        $this->sendOutput('}');
    }

    public function outputJsonArrayStart()
    {
        $this->sendOutput('[');
    }

    public function outputJsonArrayEnd()
    {
        $this->sendOutput(']');
    }

    public function outputRaw(string $text)
    {
        $this->sendOutput($text);
    }

    public function outputJsonKV($kv)
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

    public function outputJsonKey($key)
    {
        $this->sendOutput(json_encode((string) $key, JSON_UNESCAPED_SLASHES).':');
    }

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

    public function outputJsonSeparator()
    {
        $this->sendOutput(',');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function replaceQueryParams(Transaction $incomingTransaction): self
    {
        foreach (['select'] as $param) {
            $this->$param = $incomingTransaction->$param;
        }

        return $this;
    }

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

        switch ($lastSegment) {
            case '$metadata':
                $requiredType = MediaType::factory()->parse('application/xml');
                break;

            case '$value':
                $requestedFormat = $this->getAcceptedContentType();

                if ($requestedFormat) {
                    $requiredType = MediaType::factory()->parse($requestedFormat);
                } else {
                    $requiredType = MediaType::factory()->parse('text/plain');
                }
                break;

            case '$count':
                $requiredType = MediaType::factory()->parse('text/plain');
                break;
        }

        $requiredType->setParameter('charset', 'utf-8');
        $contentType = $requiredType->negotiate($this->getAcceptedContentType());

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
