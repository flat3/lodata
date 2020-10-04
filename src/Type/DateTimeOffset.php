<?php

namespace Flat3\OData\Type;

use DateTime;
use Exception;
use Flat3\OData\PrimitiveType;

class DateTimeOffset extends PrimitiveType
{
    protected $name = 'Edm.DateTimeOffset';
    public const DATE_FORMAT = 'c';

    /** @var ?DateTime $value */
    protected $value;

    public function toInternal($value): void
    {
        if (is_bool($value)) {
            $value = $this->getEmpty();
        }

        if ($value instanceof DateTime) {
            $this->value = $value;

            return;
        }

        try {
            $dt = new DateTime(rawurldecode($value));
            $this->value = $this->maybeNull(null === $value ? null : $this->repack($dt));
        } catch (Exception $e) {
            $this->value = $this->getEmpty();
        }
    }

    protected function getEmpty()
    {
        return (new DateTime())->setTimestamp(0);
    }

    protected function repack(DateTime $dt)
    {
        return $dt;
    }

    public function toUrl(): string
    {
        if (null === $this->value) {
            return $this::URL_NULL;
        }

        return rawurlencode($this->value->format($this::DATE_FORMAT));
    }

    public function toJson(): ?string
    {
        if (null === $this->value) {
            return null;
        }

        return $this->value->format($this::DATE_FORMAT);
    }
}
