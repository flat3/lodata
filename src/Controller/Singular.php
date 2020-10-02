<?php

namespace Flat3\OData\Controller;

use Flat3\OData\Exception\Internal\LexerException;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\NotFoundException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\Primitive;
use Flat3\OData\Property;
use Flat3\OData\Transaction;

class Singular extends Set
{
    public const path = parent::path.Lexer::OPEN_PAREN.'(.*?)?'.Lexer::CLOSE_PAREN;

    /** @var Property $key */
    protected $key;

    /** @var Primitive $id */
    protected $id;

    public function setup(Transaction $transaction): void
    {
        parent::setup($transaction);

        $id = array_shift($this->pathComponents);

        // Get the default key property
        $keyProperty = $this->entitySet->getType()->getKey();

        // Start the lexer
        $lexer = new Lexer($id);

        // Test for alternative key syntax
        $alternateKey = $lexer->maybeODataIdentifier();
        if ($alternateKey) {
            if ($lexer->maybeChar('=')) {
                // Test for referenced value syntax
                if ($lexer->maybeChar('@')) {
                    $referencedKey = $lexer->odataIdentifier();
                    $referencedValue = $transaction->getReferencedValue($referencedKey);
                    $lexer = new Lexer($referencedValue);
                }

                $keyProperty = $this->entitySet->getType()->getProperty($alternateKey);

                if ($keyProperty instanceof Property && !$keyProperty->isAlternativeKey()) {
                    throw new BadRequestException(
                        'property_not_alternative_key',
                        sprintf(
                            'The requested property (%s) is not configured as an alternative key',
                            $alternateKey
                        )
                    );
                }
            } else {
                // Captured value was not an alternative key, reset the lexer
                $lexer = new Lexer($id);
            }
        }

        if (null === $keyProperty) {
            throw new BadRequestException('no_key_property_exists', 'No key property exists for this entity set');
        }

        try {
            $value = $lexer->type($keyProperty->getType());
        } catch (LexerException $e) {
            throw BadRequestException::factory(
                'invalid_identifier_value',
                'The type of the provided identifier value was not valid for this entity type'
            )->lexer($lexer);
        }

        $this->id = new Primitive($value, $keyProperty);
        $this->key = $keyProperty;
    }

    public function getId(): Primitive
    {
        return $this->id;
    }

    public function handle(): void
    {
        $transaction = $this->transaction;
        $entitySet = $this->entitySet->factory($transaction);
        $entity = $entitySet->getEntity($this->id);
        $transaction->setContentTypeJson();

        if (null === $entity) {
            throw NotFoundException::factory('entity_not_found',
                sprintf('Entity with id (%s) not found', $this->id->toJson()))->target($this->id->toJson());
        }

        $metadata = [];

        $select = $transaction->getSelect();

        if ($select->hasValue() && !$select->isStar()) {
            $metadata['context'] = $transaction->getProjectedEntityContextUrl($this->entitySet, $select->getValue());
        } else {
            $metadata['context'] = $transaction->getEntityContextUrl($this->entitySet);
        }

        $metadata = $transaction->getMetadata()->filter($metadata);

        $transaction->getResponse()->setCallback(function () use ($transaction, $metadata, $entity) {
            $transaction->outputJsonObjectStart();

            if ($metadata) {
                $transaction->outputJsonKV($metadata);
                $transaction->outputJsonSeparator();
            }

            $entity->writeToResponse($transaction);
            $transaction->outputJsonObjectEnd();
        });
    }
}
