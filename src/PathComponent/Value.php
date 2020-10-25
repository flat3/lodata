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
use Flat3\Lodata\Transaction\MediaType;

class Value implements PipeInterface, EmitInterface
{
    /** @var Primitive $primitive */
    protected $primitive;

    /** @var Transaction $transaction */
    protected $transaction;

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

    public function response(): Response
    {
        $transaction = $this->transaction;
        $requestedFormat = $transaction->getAcceptedContentType();

        if ($requestedFormat) {
            $transaction->sendContentType(MediaType::factory()->parse($requestedFormat));
        } else {
            $transaction->configureTextResponse();
        }

        if (null === $this->primitive->get()) {
            throw new NoContentException('null_value');
        }

        return $transaction->getResponse()->setCallback(function () {
            $this->emit();
        });
    }

    public function emit(): void
    {
        $this->transaction->outputRaw($this->primitive->get());
    }

    public function setTransaction(Transaction $transaction): self
    {
        $this->transaction = $transaction;
        return $this;
    }
}
