<?php

namespace Flat3\OData\Controller;

use Flat3\OData\Exception\BadRequestLexerException;
use Flat3\OData\Exception\LexerException;
use Flat3\OData\Exception\NotFoundException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\Property;
use Flat3\OData\Transaction;

class Primitive extends Singular
{
    public const path = parent::path.Lexer::PATH_SEPARATOR.'([^/]+)';

    /** @var Property $property */
    protected $property;

    public function setup(Transaction $transaction): void
    {
        parent::setup($transaction);

        $property = array_shift($this->pathComponents);
        $lexer = new Lexer($property);

        try {
            $property = $lexer->odataIdentifier();
        } catch (LexerException $e) {
            throw new BadRequestLexerException('Found invalid OData property', $lexer);
        }

        $this->property = $this->store->getTypeProperty($property);

        if (!$this->property) {
            throw new NotFoundException(sprintf('The requested property (%s) was not known', $property));
        }
    }

    public function get_id(): \Flat3\OData\Primitive
    {
        return $this->id;
    }

    public function handle(): void
    {
        $transaction = $this->transaction;
        $primitive = $this->store->getPrimitive($transaction, $this->id, $this->property);
        $transaction->setContentTypeJson();

        if (!$primitive) {
            throw new NotFoundException('The requested primitive or entity set was not found');
        }

        $metadata = [
            'context' => $transaction->getPropertyValueContextUrl(
                $this->store,
                $this->id->toUrl(),
                $this->property
            )
        ];

        $metadata = $transaction->getMetadata()->filter($metadata);

        $transaction->getResponse()->setCallback(function () use ($transaction, $metadata, $primitive) {
            $transaction->outputJsonObjectStart();

            if ($metadata) {
                $transaction->outputJsonKV($metadata);
                $transaction->outputJsonSeparator();
            }

            $transaction->outputJsonKV(['value' => $primitive]);
            $transaction->outputJsonObjectEnd();
        });
    }
}
