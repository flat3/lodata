<?php

namespace Flat3\OData\PathComponent;

use Flat3\OData\Controller\Transaction;
use Flat3\OData\Exception\Internal\PathNotHandledException;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\NoContentException;
use Flat3\OData\Interfaces\EmitInterface;
use Flat3\OData\Interfaces\PipeInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        string $pathComponent,
        ?PipeInterface $argument
    ): ?PipeInterface {
        if ($pathComponent !== '$value') {
            throw new PathNotHandledException();
        }

        if (!$argument instanceof Primitive) {
            throw new BadRequestException('bad_value_argument',
                '$value must be passed a primitive value');
        }

        return new static($argument);
    }

    public function response(Transaction $transaction): StreamedResponse
    {
        $requestedFormat = $transaction->getMediaType();

        if ($requestedFormat) {
            $transaction->setContentType($requestedFormat->getOriginal());
        } else {
            $transaction->setContentTypeText();
        }

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
