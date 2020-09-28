<?php

namespace Flat3\OData\Attribute;

use Flat3\OData\Transaction;

class Format
{
    protected $format = null;

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

        if ('json' === $formatQueryOption || 'xml' === $formatQueryOption) {
            $type = 'application/'.$formatQueryOption;
        } elseif ($formatQueryOption) {
            $type = $formatQueryOption;
        } elseif ($acceptHeader) {
            $type = $acceptHeader;
        } else {
            $type = '*';
        }

        $this->format = new MediaType($type);
    }

    public function getParameter($key)
    {
        return $this->format->getParameter($key);
    }

    public function getParameterKeys()
    {
        return $this->format->getParameterKeys();
    }

    public function getSubType()
    {
        return $this->format->getSubtype();
    }
}
