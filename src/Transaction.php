<?php

namespace Flat3\OData;

use Flat3\OData\Attribute\Format;
use Flat3\OData\Attribute\IEEE754Compatible;
use Flat3\OData\Attribute\MediaType;
use Flat3\OData\Attribute\Metadata;
use Flat3\OData\Attribute\ParameterList;
use Flat3\OData\Attribute\Version;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\NotAcceptableException;
use Flat3\OData\Exception\Protocol\NotImplementedException;
use Flat3\OData\Exception\Protocol\PreconditionFailedException;
use Flat3\OData\Option\Count;
use Flat3\OData\Option\Expand;
use Flat3\OData\Option\Filter;
use Flat3\OData\Option\OrderBy;
use Flat3\OData\Option\Search;
use Flat3\OData\Option\Select;
use Flat3\OData\Option\Skip;
use Flat3\OData\Option\Top;
use Flat3\OData\Type\Boolean;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
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

    /** @var Skip $skip */
    private $skip;

    /** @var Top $top */
    private $top;

    /** @var Format $format */
    private $format;

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): self
    {
        if (!$this->request) {
            $this->request = $request;

            $this->version = new Version(
                $this->getHeader(Version::versionHeader),
                $this->getHeader(Version::maxVersionHeader)
            );

            $this->format = new Format($this);

            $this->metadata = Metadata::factory($this->format, $this->version);
            $this->preferences = new ParameterList($this->getHeader('prefer'));
            $this->ieee754compatible = new IEEE754Compatible($this->format);

            $this->count = Count::factory()->transaction($this);
            $this->expand = Expand::factory()->transaction($this);
            $this->filter = Filter::factory()->transaction($this);
            $this->orderby = OrderBy::factory()->transaction($this);
            $this->search = Search::factory()->transaction($this);
            $this->select = Select::factory()->transaction($this);
            $this->skip = Skip::factory()->transaction($this);
            $this->top = Top::factory()->transaction($this);

            foreach ($this->request->query->keys() as $param) {
                if (
                    Str::startsWith($param, '$') && !in_array(
                        $param,
                        [
                            '$apply', '$count', '$compute', '$expand', '$format', '$filter',
                            '$orderby', '$search', '$select', '$skip', '$top'
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
        }

        if (!$this->response) {
            $this->setResponse(new StreamedResponse());
        }

        $this->request = $request;

        return $this;
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
        return $this->request->query->all();
    }

    public function getResponse(): StreamedResponse
    {
        return $this->response;
    }

    public function setResponse(StreamedResponse $response): self
    {
        if (!$this->response) {
            $this->response = $response;

            if ($this->getPreference('omit-values') === 'nulls') {
                $this->preferenceApplied('omit-values', 'nulls');
            }

            $this->response->headers->set(Version::versionHeader, $this->getVersion());
            $this->response->setStatusCode(Response::HTTP_OK);
        } else {
            $this->response = $response;
        }

        return $this;
    }

    public function getVersion(): string
    {
        return $this->version->getVersion();
    }

    public function sendHttpStatus(int $code): self
    {
        $this->response->setStatusCode($code);
        return $this;
    }

    /**
     * @return ParameterList
     */
    public function getPreferences(): ParameterList
    {
        return $this->preferences;
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

        if ($omitNulls && $primitive->getInternalValue() === null && $property->isNullable()) {
            return false;
        }

        $select = $this->getSelect();
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

        if ('*' !== $this->getFormat()->getSubtype()) {
            if ($compatFormat->getSubtype() !== $this->getFormat()->getSubtype()) {
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
            array_flip($this->getFormat()->getParameterKeys())
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
     * @return Format
     */
    public function getFormat(): Format
    {
        return $this->format;
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
        return array_filter(explode('/', $this->getPath()));
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

    public function getCollectionOfEntitiesContextUrl(Store $store): string
    {
        return $this->getServiceDocumentContextUrl().'#'.$store->getIdentifier();
    }

    public function getEntityContextUrl(Store $store): string
    {
        return $this->getServiceDocumentContextUrl().'#'.$store->getIdentifier().'/$entity';
    }

    public function getSingletonContextUrl(string $singleton): string
    {
        return $this->getServiceDocumentContextUrl().'#'.$singleton;
    }

    public function getCollectionOfProjectedEntitiesContextUrl(Store $store, array $selects): string
    {
        return sprintf(
            '%s#%s(%s)',
            $this->getServiceDocumentContextUrl(),
            $store->getIdentifier(),
            join(',', $selects)
        );
    }

    public function getProjectedEntityContextUrl(Store $store, array $selects): string
    {
        return sprintf(
            '%s#%s(%s)/$entity',
            $this->getServiceDocumentContextUrl(),
            $store->getIdentifier(),
            join(',', $selects)
        );
    }

    public function getOperationResultTypeContextUrl(Type $type): string
    {
        return $this->getServiceDocumentContextUrl().'#'.$type->getEdmTypeName();
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

    public function getPropertyValueContextUrl(Store $store, $entityId, Property $property): string
    {
        return sprintf(
            "%s#%s(%s)/%s",
            $this->getServiceDocumentContextUrl(),
            $store->getIdentifier(),
            $entityId,
            $property->getIdentifier()
        );
    }

    public function getCollectionOfTypesContextUrl(Store $store, Type $type): string
    {
        return sprintf(
            '%s#%s(%s)',
            $this->getServiceDocumentContextUrl(),
            $store->getIdentifier(),
            $type->getEdmTypeName()
        );
    }

    public function getTypeContextUrl(Type $type): string
    {
        return $this->getServiceDocumentContextUrl().'#'.$type->getEdmTypeName();
    }

    public function getEntityResourceUrl(Store $store, $entityId): string
    {
        return sprintf("%s(%s)", $this->getEntityCollectionResourceUrl($store), $entityId);
    }

    public function getEntityCollectionResourceUrl(Store $store): string
    {
        return $this->getServiceDocumentResourceUrl().$store->getIdentifier();
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

    public function outputText(string $text)
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

    public function __clone()
    {
        $this->count = new Count();
        $this->expand = new Expand();
        $this->filter = new Filter();
        $this->orderby = new OrderBy();
        $this->search = new Search();
        $this->select = new Select();
        $this->skip = new Skip();
        $this->top = new Top();
    }
}
