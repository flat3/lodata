<?php

namespace Flat3\OData;

use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\NoContentException;
use Flat3\OData\Helper\Constants;
use Flat3\OData\Interfaces\PipeInterface;
use Illuminate\Http\Request;

class ActionOperation extends Operation
{
    public function getKind(): string
    {
        return 'Action';
    }

    public function getTransactionArguments(): array
    {
        $body = $this->transaction->getBody();

        if ($body && !is_array($body)) {
            throw new BadRequestException('invalid_action_arguments',
                'The arguments to the action were not correctly formed as an array');
        }

        return $body ?: [];
    }

    public function invoke(): ?PipeInterface
    {
        $this->transaction->ensureMethod(Request::METHOD_POST, 'This operation must be addressed with a POST request');
        if ($this->transaction->getBody()) {
            $this->transaction->ensureContentTypeJson();
        }

        $result = parent::invoke();

        $returnPreference = $this->transaction->getPreferenceValue(Constants::RETURN);

        if ($returnPreference === Constants::MINIMAL) {
            throw NoContentException::factory()
                ->header(Constants::PREFERENCE_APPLIED, Constants::RETURN.'='.Constants::MINIMAL);
        }

        return $result;
    }
}
