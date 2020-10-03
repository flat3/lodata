<?php

namespace Flat3\OData;

use Flat3\OData\Exception\Internal\PathNotHandledException;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\NoContentException;
use Flat3\OData\Interfaces\EmitInterface;
use Flat3\OData\Interfaces\PipeInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Value implements PipeInterface, EmitInterface
{
    /** @var Primitive $value */
    protected $value;

    public function __construct($value)
    {
        if ($this->value instanceof Primitive) {
            $this->value = $value->getValue();
            return;
        }

        $this->value = $value;
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
                '$value must be passed a primitive or primitive typed value');
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

        if (null === $this->value->getValue()) {
            throw new NoContentException('null_value');
        }

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emit($transaction);
        });
    }

    public function emit(Transaction $transaction): void
    {
        $transaction->outputRaw($this->value->getValue());
    }
}
