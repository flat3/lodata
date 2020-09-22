<?php

namespace Flat3\OData\Controller;

use Flat3\OData\Exception\Protocol\NotFoundException;
use Flat3\OData\Expression\Lexer;

class Raw extends Primitive
{
    public const path = parent::path.Lexer::PATH_SEPARATOR.'\$value';

    public function handle(): void
    {
        $transaction = $this->transaction;
        $response = $transaction->getResponse();
        $transaction->setContentTypeText();

        $primitive = $this->store->getPrimitive($transaction, $this->id, $this->property);

        if (null === $primitive->getInternalValue()) {
            throw new NotFoundException('null', 'Value is null');
        }

        $response->setCallback(function () use ($transaction, $primitive) {
            $transaction->outputText((string) $primitive->toJson());
        });
    }
}
