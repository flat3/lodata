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
        $this->serviceUri = rtrim($serviceUri, '/');

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
     * Discovers Schema and Annotations of the `$metadata` file for
     * the service.
     */
    public function discover(Model $model): Model
    {
        // override this function to register all of your $metadata capabilities
        return $model;
    }
}