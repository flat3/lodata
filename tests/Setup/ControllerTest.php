<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Setup;

use Flat3\Lodata\Controller\Request as LRequest;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;
use Illuminate\Routing\Controller;

class ControllerTest extends TestCase
{
    use WithNumericCollectionDriver;

    protected function defineRoutes($router)
    {
        $router->prefix('users')->group(function ($router) {
            $router->any("query{path}", [UserController::class, 'query'])->where('path', '(.*)');
        });
    }

    public function test_set()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/users/query', false)
                ->metadata(MetadataType\Full::name)
        );
    }

    public function test_entity()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/users/query(1)', false)
                ->metadata(MetadataType\Full::name)
        );
    }
}

class UserController extends Controller
{
    public function query(LRequest $request, Transaction $transaction, ?string $path = '')
    {
        $request->setPath('/odata/passengers'.$path);

        return $transaction->initialize($request)->execute();
    }
}