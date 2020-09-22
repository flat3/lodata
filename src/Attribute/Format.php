<?php

namespace Flat3\OData\Attribute;

class Format extends MediaType
{
    /**
     * Format constructor.
     *
     * Parse the requested format
     * https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_RequestingtheJSONFormat
     *
     * @param  string|null  $formatQueryOption
     * @param  string|null  $accept_header
     */
    public function __construct(?string $formatQueryOption, ?string $accept_header)
    {
        if ('json' === $formatQueryOption || 'xml' === $formatQueryOption) {
            $type = 'application/'.$formatQueryOption;
        } elseif ($formatQueryOption) {
            $type = $formatQueryOption;
        } elseif ($accept_header) {
            $type = $accept_header;
        } else {
            $type = '*';
        }

        parent::__construct($type);
    }
}
