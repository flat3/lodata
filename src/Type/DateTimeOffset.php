<?php

namespace Flat3\Lodata\Type;

use DateTime;
use Exception;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\PrimitiveType;

class DateTimeOffset extends PrimitiveType
{
    protected $identifier = 'Edm.DateTimeOffset';
    public const DATE_FORMAT = 'c';

    /** @var ?DateTime $value */
    protected $value;

    public function set($value): self
    {
        parent::set($value);

        if (is_bool($value)) {
            $value = $this->getEmpty();
        }

        if ($value instanceof DateTime) {
            $this->value = $value;

            return $this;
        }

        try {
            $dt = new DateTime(rawurldecode($value));
            $this->value = $this->maybeNull(null === $value ? null : $this->repack($dt));
        } catch (Exception $e) {
            $this->value = $this->getEmpty();
        }

        return $this;
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
            return Constants::NULL;
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
