<?php

namespace Flat3\OData\Attribute;

use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Transaction;
use Illuminate\Support\Str;

class Format
{
    protected $mediaType = null;

    /**
     * Format constructor.
     *
     * Parse the requested format
     * https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_RequestingtheJSONFormat
     * @param  Transaction  $transaction
     */
    public function __construct(Transaction $transaction)
    {
        $formatQueryOption = $transaction->getSystemQueryOption('format');
        $acceptHeader = $transaction->getHeader('accept');

        if (Str::startsWith($formatQueryOption, ['json', 'xml'])) {
            if (!in_array($formatQueryOption, ['json', 'xml'])) {
                throw new BadRequestException(
                    'invalid_short_format',
                    'When using a short $format option, parameters cannot be used'
                );
            }

            $type = 'application/'.$formatQueryOption;
        } elseif ($formatQueryOption) {
            $type = $formatQueryOption;
        } elseif ($acceptHeader) {
            $type = $acceptHeader;
        } else {
            $type = '*';
        }

        $this->mediaType = new MediaType($type);
    }

    public function getOriginal(): string
    {
        return $this->mediaType->getOriginal();
    }

    public function getParameter($key)
    {
        return $this->mediaType->getParameter($key);
    }

    public function getParameterKeys()
    {
        return $this->mediaType->getParameterKeys();
    }

    public function getSubType()
    {
        return $this->mediaType->getSubtype();
    }
}
