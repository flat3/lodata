<?php

namespace Flat3\OData\Controller;

use Flat3\OData\PathComponent\Primitive;
use Flat3\OData\EntitySet;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\NotAcceptableException;
use Flat3\OData\Exception\Protocol\NotFoundException;
use Flat3\OData\Exception\Protocol\NotImplementedException;
use Flat3\OData\Exception\Protocol\PreconditionFailedException;
use Flat3\OData\ServiceProvider;
use Flat3\OData\Transaction\IEEE754Compatible;
use Flat3\OData\Transaction\MediaType;
use Flat3\OData\Transaction\Metadata;
use Flat3\OData\Transaction\Option\Count;
use Flat3\OData\Transaction\Option\Expand;
use Flat3\OData\Transaction\Option\Filter;
use Flat3\OData\Transaction\Option\Format;
use Flat3\OData\Transaction\Option\OrderBy;
use Flat3\OData\Transaction\Option\SchemaVersion;
use Flat3\OData\Transaction\Option\Search;
use Flat3\OData\Transaction\Option\Select;
use Flat3\OData\Transaction\Option\Skip;
use Flat3\OData\Transaction\Option\Top;
use Flat3\OData\Transaction\ParameterList;
use Flat3\OData\Transaction\Version;
use Flat3\OData\Type;
use Flat3\OData\Type\Boolean;
use Flat3\OData\Type\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class Transaction
 *
 * @package Flat3\OData
 */
class Transaction
{
    /** @var Request $request */
    private $request;

    /** @var StreamedResponse $response */
    private $response;

    /** @var Version $version */
    private $version;

    /** @var ParameterList $preferences */
    private $preferences;

    /** @var \Flat3\OData\PathComponent\Metadata $metadata */
    private $metadata;

    /** @var IEEE754Compatible $ieee754compatible */
    private $ieee754compatible;

    /** @var \Flat3\OData\PathComponent\Count $count */
    private $count;

    /** @var Expand $expand */
    private $expand;

    /** @var Filter $filter */
    private $filter;

    /** @var \Flat3\OData\Transaction\Option\OrderBy $orderby */
    private $orderby;

    /** @var Search $search */
    private $search;

    /** @var Select $select */
    private $select;

    /** @var \Flat3\OData\Transaction\Option\Format $format */
    private $format;

    /** @var Skip $skip */
    private $skip;

    /** @var Top $top */
    private $top;

    /** @var MediaType $mediaType */
    private $mediaType;

    /** @var SchemaVersion $schemaVersion */
    private $schemaVersion;

    public function __construct()
    {
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

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function initialize(Request $request): self
    {
        $this->setRequest($request);
        $this->response = new StreamedResponse();

        $this->version = new Version(
            $this->getHeader(Version::versionHeader),
            $this->getHeader(Version::maxVersionHeader)
        );

        $acceptHeader = $this->getHeader('accept');

        $formatQueryOption = $this->getFormat()->getValue();
        if (Str::startsWith($formatQueryOption, ['json', 'xml'])) {
            if (!in_array($formatQueryOption, ['json', 'xml'])) {
                throw new BadRequestException(
                    'invalid_short_format',
                    'When using a short $format option, parameters cannot be used'
                );
            }

            $type = 'application/'.$formatQueryOption;
        } elseif ($formatQueryOption) {
            $type = $formatQueryOption;
        } elseif ($acceptHeader) {
            $type = $acceptHeader;
        } else {
            $type = '*';
        }

        $this->mediaType = new MediaType($type);
        $this->metadata = Metadata::factory($this->mediaType, $this->version);
        $this->preferences = new ParameterList($this->getHeader('prefer'));
        $this->ieee754compatible = new IEEE754Compatible($this->mediaType);

        foreach ($this->request->query->keys() as $param) {
            if (
                Str::startsWith($param, '$') && !in_array(
                    $param,
                    [
                        '$apply', '$count', '$compute', '$expand', '$format', '$filter',
                        '$orderby', '$search', '$select', '$skip', '$top', '$schemaversion',
                    ]
                )
            ) {
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

        if ($this->getHeader('isolation') || $this->getHeader('odata-isolation')) {
            throw new PreconditionFailedException('isolation_not_supported', 'Isolation is not supported');
        }

        if ($this->schemaVersion->hasValue() && $this->schemaVersion->getValue() !== '*') {
            throw new NotFoundException(
                'schema_version_not_found',
                'The requested schema version is not available'
            );
        }

        if ($this->getPreference('omit-values') === 'nulls') {
            $this->preferenceApplied('omit-values', 'nulls');
        }

        $this->response->headers->set(Version::versionHeader, $this->getVersion());
        $this->response->setStatusCode(Response::HTTP_OK);

        return $this;
    }

    public function setRequest(Request $request): self
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

    public function subTransaction(Request $request): self
    {
        if (!$this->request) {
            throw new RuntimeException('This transaction has not been initialised');
        }

        $transaction = clone $this;
        $transaction->setRequest($request);

        return $transaction;
    }

    public function getHeader($key): ?string
    {
        return $this->request->header($key);
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
        return $query instanceof InputBag ? $query->all() : [];
    }

    public function getResponse(): StreamedResponse
    {
        return $this->response;
    }

    public function getVersion(): string
    {
        return $this->version->getVersion();
    }

    /**
     * @return ParameterList
     */
    public function getPreferences(): ParameterList
    {
        return $this->preferences;
    }

    /**
     * @return \Flat3\OData\PathComponent\Count
     */
    public function getCount(): Count
    {
        return $this->count;
    }

    /**
     * @return \Flat3\OData\Transaction\Option\Expand
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
     * @return \Flat3\OData\Transaction\Option\OrderBy
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

    public function getIEEE754Compatibility(): IEEE754Compatible
    {
        return $this->ieee754compatible;
    }

    public function preferenceApplied($key, $value): self
    {
        $this->response->headers->set('preference-applied', sprintf('%s=%s', $key, $value));
        $this->response->headers->set('vary', 'prefer', true);

        return $this;
    }

    public function getPreference(string $preference)
    {
        return $this->preferences->getParameter($preference) ?: $this->preferences->getParameter('odata.'.$preference);
    }

    public function shouldEmitPrimitive(?Primitive $primitive = null): bool
    {
        if (null === $primitive) {
            return false;
        }

        $property = $primitive->getProperty();

        $omitNulls = $this->getPreference('omit-values') === 'nulls';

        if ($omitNulls && $primitive->get() === null && $property->isNullable()) {
            return false;
        }

        $select = $this->getSelect();

        if ($select->isStar() || !$select->hasValue()) {
            return true;
        }

        $selected = $select->getValue();

        if ($selected) {
            if (!in_array((string) $property, $selected)) {
                return false;
            }
        }

        return true;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function setContentTypeXml()
    {
        $this->setContentType('application/xml');
    }

    public function setContentType($contentType): self
    {
        $compatFormat = new MediaType($contentType);

        if ('*' !== $this->mediaType->getSubtype()) {
            if ($compatFormat->getSubtype() !== $this->mediaType->getSubtype()) {
                throw new NotAcceptableException('unsupported_content_type',
                    'This route does not support the requested content type');
            }
        }

        $contentTypeAttributes = [
            'charset' => 'utf-8',
        ];

        if ('application/json' === $contentType) {
            $contentTypeAttributes = array_merge($contentTypeAttributes, [
                'odata.streaming' => Boolean::URL_TRUE,
                'odata.metadata' => (string) $this->metadata,
                'IEEE754Compatible' => (string) $this->ieee754compatible,
            ]);
        }

        $contentTypeAttributes = array_intersect_key(
            $contentTypeAttributes,
            array_flip($this->mediaType->getParameterKeys())
        );

        if ($contentTypeAttributes) {
            $contentType .= ';'.http_build_query($contentTypeAttributes, '', ';');
        }

        $this->sendContentType($contentType);

        return $this;
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
     * @return \Flat3\OData\Transaction\Option\Format
     */
    public function getFormat(): Format
    {
        return $this->format;
    }

    public function getMediaType(): MediaType
    {
        return $this->mediaType;
    }

    public function sendContentType(string $contentType): self
    {
        $this->sendHeader('content-type', $contentType);

        return $this;
    }

    public function sendHeader(string $key, string $value): self
    {
        $this->response->headers->set($key, $value);

        return $this;
    }

    public function setContentTypeText(): self
    {
        $this->setContentType('text/plain');

        return $this;
    }

    public function setContentTypeJson(): self
    {
        $this->setContentType('application/json');

        return $this;
    }

    public function getPathComponents(): array
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
            $path = str_replace('%'.str_pad(dechex(ord($unreservedChar)), 2, '0', STR_PAD_LEFT), $unreservedChar,
                $path);
        }

        return $path;
    }

    public function getRequestPath(): string
    {
        $route = ServiceProvider::route();
        return Str::substr($this->request->path(), strlen($route));
    }

    public function getReferencedValue(string $key): ?string
    {
        $value = $this->getQueryParams()['@'.ltrim($key, '@')] ?? null;

        if (null === $value) {
            throw new BadRequestException('reference_value_missing',
                sprintf('The requested reference value %s did not exist', $key));
        }

        return $value;
    }

    public function getMethod(): string
    {
        return $this->request->method();
    }

    /**
     * Get the service document context URL
     *
     * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_ServiceDocument
     *
     * @return string
     */
    public function getServiceDocumentContextUrl(): string
    {
        return $this->getServiceDocumentResourceUrl().'$metadata';
    }

    public function getCollectionOfEntitiesContextUrl(EntitySet $entitySet): string
    {
        return $this->getServiceDocumentContextUrl().'#'.$entitySet->getIdentifier();
    }

    public function getEntityContextUrl(EntitySet $entitySet): string
    {
        return $this->getServiceDocumentContextUrl().'#'.$entitySet->getIdentifier().'/$entity';
    }

    public function getSingletonContextUrl(string $singleton): string
    {
        return $this->getServiceDocumentContextUrl().'#'.$singleton;
    }

    public function getCollectionOfProjectedEntitiesContextUrl(EntitySet $entitySet, array $selects): string
    {
        return sprintf(
            '%s#%s(%s)',
            $this->getServiceDocumentContextUrl(),
            $entitySet->getIdentifier(),
            join(',', $selects)
        );
    }

    public function getProjectedEntityContextUrl(EntitySet $entitySet, array $selects): string
    {
        return sprintf(
            '%s#%s(%s)/$entity',
            $this->getServiceDocumentContextUrl(),
            $entitySet->getIdentifier(),
            join(',', $selects)
        );
    }

    public function getOperationResultTypeContextUrl(Primitive $type): string
    {
        return $this->getServiceDocumentContextUrl().'#'.$type->getName();
    }

    /**
     * Get the service document URL
     *
     * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_ServiceDocument
     *
     * @return string
     */
    public function getServiceDocumentResourceUrl(): string
    {
        return ServiceProvider::restEndpoint();
    }

    public function getPropertyValueContextUrl(EntitySet $entitySet, $entityId, Property $property): string
    {
        return sprintf(
            "%s#%s(%s)/%s",
            $this->getServiceDocumentContextUrl(),
            $entitySet->getIdentifier(),
            $entityId,
            $property->getIdentifier()
        );
    }

    public function getCollectionOfTypesContextUrl(EntitySet $entitySet, Primitive $type): string
    {
        return sprintf(
            '%s#%s(%s)',
            $this->getServiceDocumentContextUrl(),
            $entitySet->getIdentifier(),
            $type->getName()
        );
    }

    public function getTypeContextUrl(Type $type): string
    {
        return $this->getServiceDocumentContextUrl().'#'.$type->getName();
    }

    public function getEntityResourceUrl(EntitySet $entitySet, $entityId): string
    {
        return sprintf("%s(%s)", $this->getEntityCollectionResourceUrl($entitySet), $entityId);
    }

    public function getEntityCollectionResourceUrl(EntitySet $entitySet): string
    {
        return $this->getServiceDocumentResourceUrl().$entitySet->getIdentifier();
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
        $this->sendOutput(json_encode((string) $key).':');
    }

    public function outputJsonValue($value)
    {
        if ($value instanceof Primitive) {
            $value = $this->ieee754compatible->isTrue() ? $value->toJsonIeee754() : $value->toJson();
        }

        $this->sendOutput(json_encode($value));
    }

    public function outputJsonSeparator()
    {
        $this->sendOutput(',');
    }
}
