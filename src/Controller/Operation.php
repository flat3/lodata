<?php

namespace Flat3\OData\Controller;

use Flat3\OData\DataModel;
use Flat3\OData\Entity;
use Flat3\OData\EntitySet;
use Flat3\OData\Exception\Internal\LexerException;
use Flat3\OData\Exception\Internal\PathNotHandledException;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\Operation\Argument;
use Flat3\OData\Transaction;
use Flat3\OData\Type;
use Illuminate\Contracts\Container\BindingResolutionException;

class Operation extends Handler
{
    public const path = parent::path.Lexer::ODATA_IDENTIFIER.Lexer::OPEN_PAREN.'(.*?)?'.Lexer::CLOSE_PAREN;

    /** @var \Flat3\OData\Operation $operation */
    protected $operation;

    /** @var string[] $args */
    protected $args;

    /**
     * @param  Transaction  $transaction
     * @throws BindingResolutionException
     */
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
                throw new BadRequestException('invalid_arguments',
                    'The arguments provided to the operation were not valid');
            }

            list($key, $value) = $kv;

            if (strpos($value, '@') === 0) {
                $value = $this->transaction->getReferencedValue($value);
            }

            return [$key => $value];
        }, array_filter(explode(',', $args))));

        $parsedArguments = [];

        /** @var Argument $argumentDefinition */
        foreach ($this->operation->getArguments() as $argumentDefinition) {
            $argumentIdentifier = $argumentDefinition->getIdentifier()->get();
            if (!array_key_exists($argumentIdentifier, $providedArguments)) {
                if (!$argumentDefinition->isNullable()) {
                    throw new BadRequestException('non_null_argument_missing',
                        sprintf('A non-null argument (%s) is missing', $argumentIdentifier));
                }

                $parsedArguments[$argumentIdentifier] = $argumentDefinition->getType()::factory(null);
                continue;
            }

            $lexer = new Lexer($providedArguments[$argumentIdentifier]);

            try {
                $parsedArguments[$argumentIdentifier] = $lexer->type($argumentDefinition->getType());
            } catch (LexerException $e) {
                throw new BadRequestException(
                    'invalid_argument_type',
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

        $metadata = [];

        $result = $this->operation->invoke($this->args);

        switch (true) {
            case $result instanceof Type:
                $metadata['context'] = $transaction->getOperationResultTypeContextUrl($result);
                break;

            case $result instanceof Entity:
                $metadata['context'] = $transaction->getEntityContextUrl($result->getStore());
                break;

            case $result instanceof EntitySet:
                $metadata['context'] = $transaction->getCollectionOfEntitiesContextUrl($result->getStore());
                break;

            case $result instanceof \Flat3\OData\Primitive:
                $metadata['context'] = $transaction->getPropertyValueContextUrl(
                    $result->getEntity()->getStore(),
                    $result->getEntity()->getEntityId()->toUrl(),
                    $result->getProperty()
                );
                break;

            default:
                throw new BadRequestException(
                    'bad_result_type',
                    'The result type of the operation could not be encoded into a context url'
                );
        }

        $metadata = $transaction->getMetadata()->filter($metadata);

        $response->setCallback(function () use ($transaction, $metadata, $result) {
            $transaction->outputJsonObjectStart();

            if ($metadata) {
                $transaction->outputJsonKV($metadata);
                $transaction->outputJsonSeparator();
            }

            switch (true) {
                case $result instanceof Entity:
                    $result->writeToResponse($transaction);
                    break;

                case $result instanceof EntitySet:
                    $transaction->outputJsonKey('value');
                    $transaction->outputJsonArrayStart();
                    $result->writeToResponse($transaction);
                    $transaction->outputJsonArrayEnd();
                    break;

                case $result instanceof Type:
                    $transaction->outputJsonKV(['value' => $result->toJson()]);
                    break;
            }

            $transaction->outputJsonObjectEnd();
        });
    }
}
