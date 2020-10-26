<?php

namespace Flat3\Lodata\PathComponent;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\EmitInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Primitive;

class Value implements PipeInterface, EmitInterface
{
    /** @var Primitive $primitive */
    protected $primitive;

    public function __construct(Primitive $primitive)
    {
        $this->primitive = $primitive;
    }

    public static function pipe(
        Transaction $transaction,
        string $currentComponent,
        ?string $nextComponent,
        ?PipeInterface $argument
    ): ?PipeInterface {
        if ($currentComponent !== '$value') {
            throw new PathNotHandledException();
        }

        if (!$argument instanceof PropertyValue) {
            throw new BadRequestException('bad_value_argument',
                '$value must be passed a property value');
        }

        $value = $argument->getPrimitiveValue();

        if (null === $value->get()) {
            throw new NoContentException('no_content', 'No content');
        }

        return new static($value);
    }

    public function response(Transaction $transaction): Response
    {
        if (null === $this->primitive->get()) {
            throw new NoContentException('null_value');
        }

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emit($transaction);
        });
    }

    public function emit(Transaction $transaction): void
    {
        $transaction->outputRaw($this->primitive->get());
    }
}
