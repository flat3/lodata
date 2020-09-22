<?php

namespace Flat3\OData\Controller;

use Flat3\OData\DataModel;
use Flat3\OData\Exception\BadRequestException;
use Flat3\OData\Exception\LexerException;
use Flat3\OData\Exception\PathNotHandledException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\Operation\Argument;
use Flat3\OData\Transaction;
use Flat3\OData\Type;

class Operation extends Handler
{
    public const path = parent::path.Lexer::ODATA_IDENTIFIER.Lexer::OPEN_PAREN.'(.*?)?'.Lexer::CLOSE_PAREN;

    /** @var \Flat3\OData\Operation $operation */
    protected $operation;

    /** @var string[] $args */
    protected $args;

    public function setup(Transaction $transaction): void
    {
        parent::setup($transaction);

        $operation = array_shift($this->pathComponents);
        $args = array_shift($this->pathComponents);

        /** @var DataModel $dataModel */
        $dataModel = app()->make(DataModel::class);

        $this->operation = $dataModel->getResources()->get($operation);

        if ($this->operation instanceof Operation) {
            throw new PathNotHandledException();
        }

        $providedArguments = array_merge(...array_map(function ($pair) {
            $pair = trim($pair);

            $kv = array_map('trim', explode('=', $pair));

            if (count($kv) !== 2) {
                throw new BadRequestException('The arguments provided to the operation were not valid');
            }

            list($key, $value) = $kv;

            if (strpos($value, '@') === 0) {
                $value = $this->transaction->getReferencedValue($value);
            }

            return [$key => $value];
        }, explode(',', $args)));

        $parsedArguments = [];

        /** @var Argument $argumentDefinition */
        foreach ($this->operation->getArguments() as $argumentDefinition) {
            $argumentIdentifier = $argumentDefinition->getIdentifier()->get();
            if (!array_key_exists($argumentIdentifier, $providedArguments)) {
                if (!$argumentDefinition->isNullable()) {
                    throw new BadRequestException(sprintf('A non-null argument (%s) is missing', $argumentIdentifier));
                }

                $parsedArguments[$argumentIdentifier] = $argumentDefinition->getType()->factory(null);
                continue;
            }

            $lexer = new Lexer($providedArguments[$argumentIdentifier]);

            try {
                $parsedArguments[$argumentIdentifier] = $lexer->type($argumentDefinition->getType());
            } catch (LexerException $e) {
                throw new BadRequestException(
                    sprintf(
                        'The provided argument %s was not of type %s',
                        $argumentIdentifier,
                        $argumentDefinition->getType()->getEdmTypeName()
                    )
                );
            }
        }

        $this->args = $parsedArguments;
    }

    public function handle(): void
    {
        $transaction = $this->transaction;
        $response = $transaction->getResponse();
        $transaction->setContentTypeJson();

        $metadata = [
            'context' => $transaction->getServiceDocumentContextUrl().'#',
        ];

        $result = $this->operation->invoke($this->args);

        if ($result instanceof Type) {
            $metadata['context'] .= $this->operation->getReturnType()->getEdmTypeName();

            $result = ['value' => $result->toJson()];
        }

        $metadata = $transaction->getMetadata()->filter($metadata);

        $response->setCallback(function () use ($transaction, $metadata, $result) {
            $transaction->outputJsonObjectStart();
            $transaction->outputJsonKV(array_merge($metadata, $result));
            $transaction->outputJsonObjectEnd();
        });
    }
}
