<?php

declare(strict_types=1);

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\NotAcceptableException;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResponseInterface;
use Flat3\Lodata\Transaction\MediaType;
use Flat3\Lodata\Transaction\MediaTypes;
use Illuminate\Http\Request;

/**
 * Query
 * @package Flat3\Lodata\PathSegment
 */
class Query implements PipeInterface
{
    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): PipeInterface {
        if ($currentSegment !== '$query') {
            throw new PathNotHandledException();
        }

        $transaction->assertMethod(Request::METHOD_POST);

        if (!MediaTypes::negotiate(
            MediaTypes::factory(MediaType::text),
            (new MediaTypes)->add($transaction->getProvidedContentType())
        )) {
            throw new NotAcceptableException(
                'unsupported_content_type',
                'Requests to this endpoint must use the text/plain content type'
            );
        }

        if (!$argument instanceof ResponseInterface) {
            throw new BadRequestException('bad_argument', 'The provided argument could not provide a response');
        }

        parse_str($transaction->getBody(), $query);
        $request = $transaction->getRequest();
        $request->query->replace($query);
        $request->setContent('');
        $request->setMethod(Request::METHOD_GET);
        $transaction->initialize($request);

        return $argument;
    }
}
