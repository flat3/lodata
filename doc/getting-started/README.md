# Quick start

This page gives you a three step quick start to getting your data exposed through an OData API. Once you're up and running
check out the rest of the docs!

## Step 1: Installation

Install Lodata into your Laravel application using [Composer](https://getcomposer.org)

<div data-event-label="composer-install">

```sh
composer require flat3/lodata
```

</div>

Now (re)start your app. The OData API endpoint will be available at: [`http://127.0.0.1:8000/odata/`](http://127.0.0.1:8000/odata/)
(or whichever URL prefix your application normally runs on).

Accessing that endpoint in a browser or an API client such as [Postman](https://www.postman.com/product/api-client/) will show you
the [Service Document](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_ServiceDocumentRequest)
that describes the services available at this endpoint. This will show an empty array of services at the moment.

## Step 2: Discovery

The first thing we'll try is exposing the data managed by an Eloquent model.
We can use auto-discovery to introspect the schema and find the available fields.

As we're just starting out we'll use the existing service provider at `app/Providers/AppServiceProvider.php`.

Open this file and add the following to the `boot()` method.

```php
\Lodata::discover(\App\Models\User::class)
```

You can now access [`http://127.0.0.1:8000/odata/Users`](http://127.0.0.1:8000/odata/Users) and see the users in your database.
Note that the properties of the model have been discovered, and their types automatically detected.

Lodata uses the same casing and pluralisation approach as Laravel, and OData URLs are **case-sensitive**.
Therefore, the `User` model is exposed as `Users` in the URL.

## Step 3: Try a query

OData has an extensive set of filtering, searching and sorting capabilities.

We'll exercise it with this request that returns the first three users that have email addresses ending in `@gmail.com`, sorted by recently created first, and we're only interested in the name, email and created_at properties:

[`http://127.0.0.1:8000/odata/Users?filter=endswith(email, '@gmail.com')&top=3&orderby=created_at desc&select=name,email,created_at`](http://127.0.0.1:8000/odata/Users?filter=endswith%28email,%20'@gmail.com'%29&top=3&orderby=created_at%20desc&select=name,email,created_at)

This might look a bit complex at first, but we'll go into more detail on the available query options later in this documentation.

---

Now you're up and running! There's a ton more you can do with OData, have a look through the rest of the docs to find out more...