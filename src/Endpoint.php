<?php

namespace Flat3\Lodata;

class Endpoint
{
    /**
     * @var string $serviceUri the &lt;service-uri> of the <code>Flat3\Lodata\Model</code>.
     */
    protected $serviceUri;

    /**
     * @var string $route the route prefix configured in <code>'lodata.prefix'</code>
     */
    protected $route;

    /**
     * @var string $endpoint the full url to the ODataService endpoint
     */
    protected $endpoint;

    public function __construct(string $serviceUri)
    {
        $this->serviceUri = trim($serviceUri, '/');

        $prefix = rtrim(config('lodata.prefix'), '/');
        $this->route = ('' === $serviceUri)
            ? $prefix
            : $prefix . '/' . $this->serviceUri;

        $this->endpoint = url($this->route) . '/';
    }

    /**
     * @return string the path within the odata URI space, like in
     * https://<server>:<port>/<config('lodata.prefix')>/<service-uri>/$metadata
     */
    public function endpoint(): string
    {
        return $this->endpoint;
    }

    public function route(): string
    {
        return $this->route;
    }

    /**
     * This method is intended to be overridden by subclasses.
     *
     * The value of the function will be presented in the Schema Namespace attribute,
     * https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Schema
     *
     * @return string
     */
    public function getNamespace(): string
    {
        // override this function to set Schema Namespace attribute
        return config('lodata.namespace');
    }

    /**
     * This method is intended to be overridden by subclasses.
     *
     * Discovers Schema and Annotations of the `$metadata` file for
     * the service.
     */
    public function discover(Model $model): Model
    {
        // override this function to register all of your $metadata capabilities
        return $model;
    }
}