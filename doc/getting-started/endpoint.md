
# Service Endpoints

> **Prerequisite**: You’ve already published the `lodata.php` config file into your Laravel project using `php artisan vendor:publish`.

## Overview

By default, `flat3/lodata` exposes a **single global service endpoint**. However, for modular applications or domain-driven designs, you may want to expose **multiple, isolated OData service endpoints** — one per module, feature, or bounded context.

This is where **service endpoints** come in. They allow you to split your schema into smaller, focused units, each with its own `$metadata` document and queryable surface.

## Defining Multiple Endpoints

You can define service endpoints by registering them in your `config/lodata.php` configuration file:

```php
/**
 * At the end of config/lodata.php
 */
'endpoints' => [
    'projects' => \App\Projects\ProjectEndpoint::class,
],
```

With this configuration, a separate `$metadata` document becomes available at:

```
https://<server>:<port>/<lodata.prefix>/projects/$metadata
```

If the `endpoints` array is left empty (the default), only a single global endpoint is created under the configured `lodata.prefix`.

## Endpoint Discovery

Each service endpoint class implements the `ServiceEndpointInterface`. This includes a `discover()` method where you define which entities, types, and annotations should be exposed by this endpoint.

This gives you fine-grained control over what each endpoint exposes.

```php
use App\Models\Contact;
use Flat3\Lodata\Model;

/**
 * Discover schema elements and annotations for the service endpoint.
 */
public function discover(Model $model): Model
{
    // Register all exposed entity sets or types
    $model->discover(Contact::class);
    // Add more types or annotations here...

    return $model;
}
```

### Performance Benefit

The `discover()` method is only invoked **when an actual OData request targets the specific service endpoint**. It is **not** triggered for standard Laravel routes outside the OData URI space (such as `/web`, `/api`, or other unrelated routes). This behavior ensures that your application remains lightweight during boot and only loads schema definitions when they are explicitly required.

> ✅ This optimization also applies to the **default (global) service endpoint** — its `discover()` method is likewise only evaluated on-demand during OData requests.

This design keeps your application performant, especially in modular or multi-endpoint setups, by avoiding unnecessary processing for unrelated HTTP traffic.

## Serving Pre-Generated $metadata Files

In addition to dynamic schema generation, you can optionally serve a **pre-generated `$metadata.xml` file**. This is especially useful when:

- You want to include **custom annotations** that are not easily represented in PHP code.
- You have **external tools** that generate the schema.
- You prefer **fine-tuned control** over the metadata document.

To enable this, implement the `cachedMetadataXMLPath()` method in your endpoint class:

```php
public function cachedMetadataXMLPath(): ?string
{
    return base_path('odata/metadata-projects.xml');
}
```

If this method returns a valid file path, `lodata` will serve this file directly when `$metadata` is requested, bypassing the `discover()` logic.

If it returns `null` (default), the schema will be generated dynamically from the `discover()` method.

## Summary

| Feature                  | Dynamic (`discover`) | Static (`cachedMetadataXMLPath`) |
|--------------------------|----------------------|-----------------------------------|
| Schema definition        | In PHP               | In XML file                       |
| Supports annotations     | Basic                | Full (manual control)             |
| Performance optimized    | Yes                  | Yes                               |
| Best for                 | Laravel-native setup | SAP integration, fine-tuned CSDL  |

Great! Here's an additional **section for your documentation** that walks readers through the complete sample endpoint implementation, ties it back to the configuration, and shows how it integrates into the actual request flow.

## Sample: Defining a `ProjectEndpoint`

Let’s walk through a concrete example of how to define and use a modular service endpoint in your Laravel app — focused on the **Project** domain.

### Step 1: Define the Custom Endpoint Class

To create a service that reflects the specific logic, scope, and metadata of your Project domain, you extend the `Flat3\Lodata\Endpoint` base class. You’re not required to implement any abstract methods. Instead, you override the ones that make this service distinct.

Here’s a minimal yet complete example:

```php
<?php

namespace App\Endpoints;

use App\Models\Project;
use Flat3\Lodata\Endpoint;
use Flat3\Lodata\Model;

/**
 * OData service endpoint for project-related data.
 *
 * Exposes a modular OData service at /projects with its own metadata namespace.
 */
class ProjectEndpoint extends Endpoint
{
    /**
     * Define the namespace used in the <Schema> element of $metadata.
     */
    public function namespace(): string
    {
        return 'ProjectService';
    }

    /**
     * Optionally return a static metadata XML file.
     * If null, dynamic discovery via discover() is used.
     */
    public function cachedMetadataXMLPath(): ?string
    {
        return resource_path('meta/ProjectService.xml');
    }

    /**
     * Register entities and types to expose through this endpoint.
     */
    public function discover(Model $model): Model
    {
        $model->discover(Project::class);

        return $model;
    }
}
```

> ✅ **You only override what’s relevant to your endpoint.** This makes it easy to tailor each endpoint to a specific bounded context without unnecessary boilerplate.

### Step 2: Register the Endpoint and Define Its URI Prefix

In your `config/lodata.php`, register the custom endpoint under the `endpoints` array:

```php
'endpoints' => [
    'projects' => \App\Endpoints\ProjectEndpoint::class,
],
```

> 🧩 The **key** (`projects`) is not just a label — it becomes the **URI prefix** for this endpoint. In this case, all OData requests to `/odata/projects` will be routed to your `ProjectEndpoint`.

This results in:

- `$metadata` available at:  
  `https://<server>:<port>/<lodata.prefix>/projects/$metadata`

- Entity sets exposed through:  
  `https://<server>:<port>/<lodata.prefix>/projects/Projects`

This convention gives you **clear, readable URLs** and enables **modular, multi-service APIs** without extra routing configuration.

### Step 3: Serve Dynamic or Static Metadata

The framework will:

- Call `cachedMetadataXMLPath()` first.  
  If a file path is returned and the file exists, it will serve that file directly.
- Otherwise, it will fall back to the `discover()` method to dynamically register entities, types, and annotations.

This hybrid approach gives you **maximum flexibility** — allowing you to combine automated model discovery with the full expressive power of hand-authored metadata if needed.

## ✅ What You Get

With just a few lines of configuration, you now have:

- A **cleanly separated OData service** for the `Project` module.
- **Independent metadata** for documentation and integration.
- A fast and **on-demand schema bootstrapping** process.
- Full **control over discoverability** and **extensibility**.

You can now repeat this pattern for other domains (e.g., `contacts`, `finance`, `hr`) to keep your OData services modular, testable, and scalable.

Perfect! Let’s build on this momentum and add a **visual + narrative section** that ties the whole flow together — showing how all the moving parts interact:

## How Everything Connects

When you define a custom OData service endpoint, you’re essentially configuring a **self-contained API module** with its own URI, schema, metadata, and behavior. Let’s zoom out and see how the elements work together.

### Flow Overview

```
[ config/lodata.php ]         →        [ ProjectEndpoint class ]
          │                                      │
          ▼                                      ▼
  'projects' => ProjectEndpoint::class    ──►  defines:
                                             - namespace()
                                             - discover()
                                             - cachedMetadataXMLPath()

          │                                      │
          ▼                                      ▼
   URI: /odata/projects/$metadata          OData Schema (XML or dynamic) 
```

### The Building Blocks

| Component                          | Purpose                                                                 |
|-----------------------------------|-------------------------------------------------------------------------|
| **`config/lodata.php`**           | Registers all endpoints and defines the URI prefix for each one         |
| **Key: `'projects'`**             | Becomes part of the URL: `/odata/projects/`                             |
| **`ProjectEndpoint` class**       | Defines what the endpoint serves and how                                |
| **`namespace()`**                 | Injects the `<Schema Namespace="ProjectService" />` into `$metadata`    |
| **`discover(Model $model)`**      | Dynamically registers entities like `Project::class`                    |
| **`cachedMetadataXMLPath()`**     | Optionally returns a pre-generated CSDL XML file                        |
| **OData request**                 | Triggers loading of this endpoint’s metadata and data                   |

## Example: Request Lifecycle

Let’s break down how the enhanced flow would look for an actual **entity set access**, such as

```
GET /odata/projects/Costcenters
```

This is about a **data request** for a specific entity set. Here's how the full lifecycle plays out. From config to response.

### Enhanced Flow for `/odata/projects/Costcenters`

```
                            ┌─────────────────────────────────────────────────────┐
                            │    HTTP GET /odata/projects/Costcenters             │
                            └─────────────────────────────────────────────────────┘
                                                │
                                                ▼
┌────────────────────────────────────────┐   [Routing Layer] matches
│ config/lodata.php                      │── 'projects' key
│                                        │
│ 'projects' => ProjectEndpoint::class,  │
└────────────────────────────────────────┘
                                                │
                                                ▼
                            ┌──────────────────────────────────────┐
                            │   New ProjectEndpoint instance       │
                            └──────────────────────────────────────┘
                                                │
                              (cachedMetadataXMLPath() not used here)
                                                │
                                                ▼
                    ┌───────────────────────────────────────────────┐
                    │   discover(Model $model) is invoked           │
                    │   → model->discover(Project::class)           │
                    │   → model->discover(Costcenter::class)        │
                    └───────────────────────────────────────────────┘
                                                │
                                                ▼
                        ┌──────────────────────────────────────┐
                        │   Lodata resolves the URI segment:   │
                        │   `Costcenters`                      │
                        └──────────────────────────────────────┘
                                                │
                          (via the registered EntitySet name for Costcenter)
                                                │
                                                ▼
                      ┌───────────────────────────────────────────────┐
                      │  Query engine builds and executes the query   │
                      │  using the underlying Eloquent model          │
                      └───────────────────────────────────────────────┘
                                                │
                                                ▼
                    ┌────────────────────────────────────────────┐
                    │   Response is serialized into JSON or XML  │
                    │   according to Accept header               │
                    └────────────────────────────────────────────┘
                                                │
                                                ▼
                🔁 JSON (default) or Atom/XML payload with Costcenter entities

```

### What Must Be in Place for This to Work

| Requirement                                    | Description                                                                 |
|-----------------------------------------------|-----------------------------------------------------------------------------|
| `ProjectEndpoint::discover()`                 | Must register `Costcenter::class` via `$model->discover(...)`               |
| `Costcenter` model                            | Can be a **standard Laravel Eloquent model** – no special base class needed |
| `EntitySet` name                              | Must match the URI segment: `Costcenters`                                   |
| URI case sensitivity                          | Lodata uses the identifier names → ensure entity names match URI segments   |
| Accept header                                 | Optional – defaults to JSON if none is provided                             |

Absolutely! Here's a fully integrated and refined section that combines both the **"What This Enables"** and **"Summary"** parts into one cohesive, value-driven conclusion:

## What Modular Service Endpoints Enable

Modular service endpoints give you precise control over how your OData APIs are structured, documented, and consumed. With just a small configuration change and a focused endpoint class, you unlock a powerful set of capabilities:

- **Modular APIs** — Define multiple endpoints, each exposing only the entities and operations relevant to a specific domain (e.g., `projects`, `contacts`, `finance`).
- **Clean, discoverable URLs** — Support intuitive REST-style routes like `/odata/projects/Costcenters?$filter=active eq true`, with automatic support for `$filter`, `$expand`, `$orderby`, and paging.
- **Endpoint-specific metadata** — Each service exposes its own `$metadata`, either dynamically generated or served from a pre-generated XML file — perfect for integration with clients that require full annotation control.
- **Schema isolation** — Maintain clean separation between domains, clients, or API versions. For example:
    - `/odata/projects/$metadata` → `ProjectService` schema
    - `/odata/finance/$metadata` → `FinanceService` schema
- **Mix and match discovery strategies** — Use dynamic schema generation via Eloquent models or inject precise, curated metadata with static CSDL files.
- **Scalable architecture** — Modular endpoints help you grow from a single-purpose API to a rich multi-domain platform — all while keeping concerns separated and maintainable.

### ✅ In Short

Modular service endpoints allow you to:

- Keep your domains cleanly separated
- Scale your API by feature, client, or team
- Provide tailored metadata per endpoint
- Mix dynamic discovery with pre-defined XML schemas
- Integrate smoothly into your Laravel app — no magic, just configuration and conventions

They’re not just a convenience, they’re a foundation for **clean, scalable, and maintainable OData APIs**.
