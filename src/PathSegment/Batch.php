<?php

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\NotAcceptableException;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EmitInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Transaction\Batch\JSON;
use Flat3\Lodata\Transaction\Batch\Multipart;
use Illuminate\Http\Request;

/**
 * Batch
 * @package Flat3\Lodata\PathSegment
 */
class Batch implements PipeInterface, EmitInterface
{
    /**
     * @var \Flat3\Lodata\Transaction\Batch $implementation
     * @internal
     */
    protected $implementation;

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): PipeInterface {
        if ($currentSegment !== '$batch') {
            throw new PathNotHandledException();
        }

        if ($argument) {
            throw new BadRequestException('batch_argument', 'Batch must be the first argument in the path');
        }

        return new self();
    }

    /**
     * Emit the batch response
     * @param  Transaction  $transaction  Transaction
     */
    public function emit(Transaction $transaction): void
    {
        $this->implementation->emit($transaction);
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $transaction->ensureMethod(Request::METHOD_POST);

        $contentType = $transaction->getAcceptedContentType() ?: $transaction->getProvidedContentType();

        switch ($contentType->getType()) {
            case 'multipart/mixed':
                $this->implementation = new Multipart();
                break;

            case 'application/json':
                $this->implementation = new JSON();
                break;

            default:
                throw new NotAcceptableException(
                    'unknown_batch_type',
                    'The requested batch content type was not known'
                );
        }

        return $this->implementation->response($transaction, $context);
    }
}
