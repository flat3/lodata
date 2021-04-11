<?php

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResponseInterface;
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
