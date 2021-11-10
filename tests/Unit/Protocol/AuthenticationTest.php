<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Illuminate\Routing\Router;

class AuthenticationTest extends TestCase
{
    public function test_default_no_auth()
    {
        $this->assertMetadataResponse(
            (new Request)
        );
    }

    public function test_requires_basic_auth()
    {
        config(['lodata.middleware' => 'auth.basic']);
        $this->withMiddleware();

        app(Router::class)->getRoutes()->get('GET')['odata{path}']->middleware(['auth.basic']);

        $this->assertUnauthorizedHttpException(
            (new Request)
        );
    }
}

