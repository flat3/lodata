<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Illuminate\Routing\Router;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthenticationTest extends TestCase
{
    public function test_default_no_auth()
    {
        $this->assertResponseSnapshot(
            (new Request)
        );
    }

    public function test_requires_basic_auth()
    {
        config(['lodata.middleware' => 'auth.basic']);
        $this->withMiddleware();
        $this->expectException(UnauthorizedHttpException::class);

        app(Router::class)->getRoutes()->get('GET')['odata{path}']->middleware(['auth.basic']);

        $this->req((new Request));
    }
}

