<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Model;

/**
 * Interface for defining a modular OData service endpoint in Laravel.
 *
 * Implementers of this interface represent individually addressable OData services.
 * Each mounted under its own URI segment and backed by a schema model.
 *
 * This enables clean separation of business domains and supports multi-endpoint
 * discovery for modular application design.
 *
 * Configuration versus Declaration
 * --------------------------------
 * The public URI segment used to expose a service is NOT determined by the
 * implementing class itself, but by the service map in `config/lodata.php`:
 *
 * ```php
 *   'endpoints' => [
 *       'users' => \App\OData\UsersEndpoint::class,
 *       'budgets' => \App\OData\BudgetsEndpoint::class,
 *   ]
 * ```
 *
 * This keeps the routing surface under application control, and avoids
 * conflicts when two modules declare the same internal segment.
 *
 * To implement an endpoint:
 *   - Extend `Flat3\Lodata\Endpoint` or implement this interface directly
 *   - Register the class in `config/lodata.php` under a unique segment key
 *   - Define the `discover()` method to expose entities via OData
 */
interface ServiceEndpointInterface
{
    /**
     * Returns the ServiceURI segment name for this OData endpoint.
     *
     * This value is used in routing and metadata resolution. It Must be globally unique.
     *
     * @return string The segment (path identifier) of the endpoint
     */
    public function serviceUri(): string;

    /**
     * Returns the fully qualified URL for this OData endpoint.
     *
     *  This includes the application host, port, and the configured segment,
     *  https://<server>:<port>/<config('lodata.prefix')>/<service-uri>/,
     *  Example: https://example.com/odata/users/
     *
     * This URL forms the base of the OData service space, and is used for navigation links
     * and metadata discovery.
     *
     * @return string The full URL of the service endpoint
     */
    public function endpoint(): string;

    /**
     * Returns the internal Laravel route path for this OData service endpoint.
     *
     * This is the relative URI path that Laravel uses to match incoming requests,
     * typically composed of the configured Lodata prefix and the service segment.
     *
     * Example: "odata/users"
     *
     * @return string Relative route path for the endpoint
     */
    public function route(): string;

    /**
     * Returns the XML namespace used in the `$metadata` document.
     *
     * This value is injected as the `Namespace` attribute of the <Schema> element
     * in the OData CSDL document, and must be globally unique per service.
     *
     * @see https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Schema
     *
     * @return string The schema namespace for the service
     */
    public function namespace(): string;

    /**
     * Returns the absolute filesystem path to a statically annotated `$metadata` file.
     *
     * This method can be overridden to provide a custom pre-generated CSDL XML file
     * for the OData metadata endpoint. If a path is returned, it will be used as-is
     * instead of dynamically generating the schema from model definitions.
     * Return `null` to fall back to automatic schema generation.
     *
     * @return string|null Full path to a static $metadata XML file, or null for dynamic generation
     */
    public function cachedMetadataXMLPath(): ?string;

    /**
     * Builds or enriches the model used for schema generation and metadata discovery.
     *
     * This method should populate the provided `Model` instance with entity sets,
     * types, annotations, and operations that define the service schema. It is
     * invoked during the metadata bootstrapping process when an OData service request
     * is processed.
     *
     * @param Model $model The schema model to populate
     * @return Model The enriched model instance representing the OData service
     */
    public function discover(Model $model): Model;
}