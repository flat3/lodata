<?php

declare(strict_types=1);

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResponseInterface;
use Flat3\Lodata\Transaction\MediaType;
use Flat3\Lodata\Transaction\MediaTypes;
use Illuminate\Http\Request;

/**
 * Metadata
 * @package Flat3\Lodata\PathSegment
 */
abstract class Metadata implements PipeInterface, ResponseInterface
{
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

        $transaction->assertMethod(Request::METHOD_GET);

        $contentType = MediaTypes::negotiate(
            $transaction->getAcceptedContentTypes(),
            MediaTypes::factory(MediaType::xml, MediaType::json)
        );

        switch ($contentType->getSubtype()) {
            case 'json':
                return new Metadata\JSON();

            default:
                return new Metadata\XML();
        }
    }
}
