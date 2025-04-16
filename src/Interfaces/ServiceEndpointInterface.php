<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Model;

/**
 * Interface for defining a custom OData service endpoint.
 *
 * Implementers can use this interface to expose a specific service under a custom path,
 * define its namespace, route behavior, and optionally provide a statically generated
 * $metadata document.
 */
interface ServiceEndpointInterface
{

    /**
     * Returns the relative endpoint identifier within the OData service URI space.
     *
     * This is the part that appears between the configured Lodata prefix and
     * the `$metadata` segment, e.g.:
     *   https://<server>:<port>/<config('lodata.prefix')>/<endpoint>/$metadata
     *
     * @return string The relative OData endpoint path
     */
    public function endpoint(): string;

    /**
     * Returns the full request route to this service endpoint.
     *
     * This typically resolves to the route path used by Laravel to handle
     * incoming requests for this specific service instance.
     *
     * @return string The full HTTP route to the endpoint
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