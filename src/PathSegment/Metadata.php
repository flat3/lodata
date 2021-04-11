<?php

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\NotAcceptableException;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResponseInterface;
use Flat3\Lodata\Transaction\Metadata\JSON;
use Flat3\Lodata\Transaction\Metadata\XML;
use Illuminate\Http\Request;

/**
 * Metadata
 * @package Flat3\Lodata\PathSegment
 */
class Metadata implements PipeInterface, ResponseInterface
{
    /**
     * @var \Flat3\Lodata\Transaction\Metadata $implementation
     * @internal
     */
    protected $implementation;

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): PipeInterface {
        if ($currentSegment !== '$metadata') {
            throw new PathNotHandledException();
        }

        if ($argument || $nextSegment) {
            throw new BadRequestException('metadata_argument', '$metadata must be the only argument in the path');
        }

        return new self();
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $transaction->assertMethod(Request::METHOD_GET);

        $contentType = $transaction->getAcceptedContentType();

        switch ($contentType->getSubtype()) {
            case 'xml':
            case '*':
                $this->implementation = new XML();
                break;

            case 'json':
                $this->implementation = new JSON();
                break;

            default:
                throw new NotAcceptableException(
                    'unknown_metadata_type',
                    'The requested metadata content type was not known'
                );
        }

        return $this->implementation->response($transaction, $context);
    }
}
