<?php

namespace Flat3\OData\Controller;

use Flat3\OData\EntitySet;
use Flat3\OData\Exception\Internal\PathNotHandledException;
use Flat3\OData\Exception\Protocol\InternalServerErrorException;
use Flat3\OData\Exception\Protocol\NoContentException;
use Flat3\OData\Exception\Protocol\NotFoundException;
use Flat3\OData\Interfaces\EmitInterface;
use Flat3\OData\Interfaces\PipeInterface;
use Flat3\OData\Operation;
use Flat3\OData\PathComponent\Count;
use Flat3\OData\PathComponent\Filter;
use Flat3\OData\PathComponent\Metadata;
use Flat3\OData\PathComponent\Service;
use Flat3\OData\PathComponent\Value;
use Flat3\OData\PrimitiveType;
use Flat3\OData\Singleton;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class Job implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /** @var PipeInterface[] $handlers */
    protected $handlers = [
        EntitySet::class,
        Metadata::class,
        Value::class,
        Count::class,
        Operation::class,
        PrimitiveType::class,
        Singleton::class,
        Filter::class,
    ];

    protected $transaction;

    public function setTransaction(Transaction $transaction): self
    {
        $this->transaction = $transaction;
        return $this;
    }

    public function handle()
    {
        $transaction = $this->transaction;
        $pathComponents = $transaction->getPathComponents();

        /** @var PipeInterface|EmitInterface $result */
        $result = null;

        if (!$pathComponents) {
            return new Service();
        }

        while ($pathComponents) {
            $currentComponent = array_shift($pathComponents);
            $nextComponent = $pathComponents[0] ?? null;

            foreach ($this->handlers as $handler) {
                try {
                    $result = $handler::pipe($transaction, $currentComponent, $nextComponent, $result);
                    continue 2;
                } catch (PathNotHandledException $e) {
                    continue;
                }
            }

            throw new NotFoundException('no_handler', 'No route handler was able to process this request');
        }

        if (null === $result) {
            throw NoContentException::factory('no_content', 'No content');
        }

        if (!$result instanceof EmitInterface) {
            throw new InternalServerErrorException(
                'cannot_emit_handler',
                'A handler returned something that could not be emitted'
            );
        }

        return $result;
    }
}