<?php

namespace Flat3\OData\Controller;

use Flat3\OData\Expression\Lexer;

class Count extends Set
{
    public const path = parent::path.Lexer::PATH_SEPARATOR.'\$count';

    public function handle(): void
    {
        $transaction = $this->transaction;
        $response = $transaction->getResponse();
        $transaction->setContentTypeText();

        $transaction->getTop()->clearValue();
        $transaction->getSkip()->clearValue();
        $transaction->getOrderBy()->clearValue();
        $transaction->getExpand()->clearValue();

        $count = $this->store->getCount($transaction);

        $response->setCallback(function () use ($transaction, $count) {
            $transaction->sendOutput($count);
        });
    }
}
