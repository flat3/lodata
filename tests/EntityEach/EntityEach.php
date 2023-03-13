<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntityEach;

use Flat3\Lodata\Entity;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation\Action;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\String_;

abstract class EntityEach extends TestCase
{
    public function test_delete_each()
    {
        $this->assertNoContent(
            (new Request)
                ->path($this->entitySetPath.'/$filter(age ge 3)/$each')
                ->delete()
        );
    }

    public function test_update_each()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'/$filter(@bar)/$each')
                ->query('@bar', 'age ge 3')
                ->patch()
                ->body(['name' => 'Oop'])
        );
    }

    public function test_action_each()
    {
        $action = new Action('testaction');
        $action->setCallable(function (Entity $entity): String_ {
            return $entity['name']->getPrimitive();
        });

        $action->setBindingParameterName('entity');
        Lodata::add($action);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'/$filter(@bar)/$each/testaction()')
                ->query('@bar', 'age ge 3')
                ->post()
        );
    }

    public function test_action_each_arg()
    {
        $action = new Action('testaction');
        $action->setCallable(function (Entity $entity, string $pfx): string {
            return $pfx.' '.$entity['name']->getPrimitive()->toMixed();
        });

        $action->setBindingParameterName('entity');
        Lodata::add($action);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'/$filter(@bar)/$each/testaction()')
                ->query('@bar', 'age ge 3')
                ->body([
                    'pfx' => 'Hello',
                ])
                ->post()
        );
    }
}
