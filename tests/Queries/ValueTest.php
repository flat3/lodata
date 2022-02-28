<?php

namespace Flat3\Lodata\Tests\Queries;

use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MediaType;
use Flat3\Lodata\Type;

class ValueTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $type = (new EntityType('stype'))
            ->addDeclaredProperty('sprop', Type::string());
        $entity = new Singleton('stest', $type);
        $entity['sprop'] = 'svalue';
        Lodata::add($entity);
    }

    public function test_raw_custom_accept()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->header('accept', 'application/octet-stream')
                ->path('/stest/sprop/$value')
        );
    }

    public function test_raw_custom_format()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->format('application/octet-stream')
                ->path('/stest/sprop/$value')
        );
    }

    public function test_raw_no_accept()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->accept('')
                ->path('/stest/sprop/$value')
        );
    }

    public function test_raw_accept_any()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->accept(MediaType::any)
                ->path('/stest/sprop/$value')
        );
    }
}