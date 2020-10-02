<?php

namespace Flat3\OData\Controller;

use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\Interfaces\CountInterface;
use Flat3\OData\Transaction;

class Count extends Set
{
    public const path = parent::path.Lexer::PATH_SEPARATOR.'\$count';

    public function setup(Transaction $transaction): void
    {
        parent::setup($transaction);

        if (!$this->entitySet instanceof CountInterface) {
            throw new BadRequestException(
                'cannot_count_entity_set',
                'The requested entity set does not support count'
            );
        }
    }

    public function handle(): void
    {
        $transaction = $this->transaction;
        $response = $transaction->getResponse();
        $transaction->setContentTypeText();

        $transaction->getTop()->clearValue();
        $transaction->getSkip()->clearValue();
        $transaction->getOrderBy()->clearValue();
        $transaction->getExpand()->clearValue();

        $count = $this->entitySet->factory($transaction)->count();

        $response->setCallback(function () use ($transaction, $count) {
            $transaction->sendOutput($count);
        });
    }
}
