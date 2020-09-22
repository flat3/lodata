<?php

namespace Flat3\OData;

use Flat3\OData\Attribute\Format;
use Flat3\OData\Attribute\IEEE754Compatible;
use Flat3\OData\Attribute\MediaType;
use Flat3\OData\Attribute\Metadata;
use Flat3\OData\Attribute\ParameterList;
use Flat3\OData\Attribute\Version;
use Flat3\OData\Exception\BadRequestException;
use Flat3\OData\Exception\NotAcceptableException;
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
            $format = new Format($this->getSystemQueryOption('format'), $this->getHeader('accept'));
            $this->metadata = Metadata::factory($format, $this->version);
            $this->preferences = new ParameterList($this->getHeader('prefer'));
            $this->ieee754compatible = new IEEE754Compatible($format);

            $this->count = (new Count())->transaction($this);
            $this->expand = (new Expand())->transaction($this);
            $this->filter = (new Filter())->transaction($this);
            $this->orderby = (new OrderBy())->transaction($this);
            $this->search = (new Search())->transaction($this);
            $this->select = (new Select())->transaction($this);
            $this->skip = (new Skip())->transaction($this);
            $this->top = (new Top())->transaction($this);
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
            $response->headers->set(Version::versionHeader, $this->getVersion());
            $response->setStatusCode(Response::HTTP_OK);
        }

        $this->response = $response;

        return $this;
    }

    public function sendHeader(string $key, string $value): self
    {
        $this->response->headers->set($key, $value);
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

    public function getPreference(string $preference)
    {
        return $this->preferences->getParameter($preference);
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function setContentTypeXml()
    {
        $this->setContentType('application/xml');
    }

    public function setContentType($contentType)
    {
        $compatFormat = new MediaType($contentType);

        if ('*' !== $this->getFormat()->getSubtype()) {
            if ($compatFormat->getSubtype() !== $this->getFormat()->getSubtype()) {
                throw new NotAcceptableException('This route does not support the requested content type');
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
    }

    /**
     * @return Format
     */
    public function getFormat(): Format
    {
        return new Format($this->getSystemQueryOption(Option\Format::param), $this->getHeader('accept'));
    }

    public function sendContentType(string $contentType)
    {
        $this->sendHeader('content-type', $contentType);
    }

    public function setContentTypeText()
    {
        $this->setContentType('text/plain');
    }

    public function setContentTypeJson()
    {
        $this->setContentType('application/json');
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
            throw new BadRequestException(sprintf('The requested reference value %s did not exist', $key));
        }

        return $value;
    }

    public function getMethod(): string
    {
        return $this->request->method();
    }

    /**
     * Get the entity context URL
     *
     * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_Entity
     *
     * @return string
     */
    public function getEntityContextUrl(Store $store): string
    {
        return $this->getEntityCollectionContextUrl($store).'/$entity';
    }

    /**
     * Get the entity collection context URL
     *
     * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_CollectionofEntities
     *
     * @param $entityId
     *
     * @return string
     */
    public function getEntityCollectionContextUrl(Store $store, $entityId = null): string
    {
        $url = $this->getServiceDocumentContextUrl().'#'.$store->getIdentifier();

        if ($entityId) {
            $url = sprintf("%s(%s)", $url, $entityId);
        }

        return $url;
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

    /**
     * Get the property value context URL
     *
     * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_PropertyValue
     *
     * @return string
     */
    public function getPropertyValueContextUrl(Store $store, $entityId, Property $property): string
    {
        return $this->getEntityCollectionContextUrl($store, $entityId).'/'.$property->getIdentifier();
    }

    /**
     * Get the entity resource URL
     *
     * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_Entity
     *
     * @param $entityId
     *
     * @return string
     */
    public function getEntityResourceUrl(Store $store, $entityId): string
    {
        return sprintf("%s(%s)", $this->getEntityCollectionResourceUrl($store), $entityId);
    }

    /**
     * Get the entity collection resource URL
     *
     * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_CollectionofEntities
     *
     * @return string
     */
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
}
