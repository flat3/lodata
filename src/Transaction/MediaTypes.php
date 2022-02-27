<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\ObjectArray;

/**
 * Media Types
 * @package Flat3\Lodata\Transaction
 */
class MediaTypes extends ObjectArray
{
    protected $types = [MediaType::class];

    /**
     * Generate a list of MediaType objects from the provided string types
     * @param  string  ...$types  List of types
     * @return MediaTypes
     */
    public static function factory(string ...$types): MediaTypes
    {
        $mediaTypes = new MediaTypes();

        foreach ($types as $type) {
            foreach (array_filter(explode(',', $type)) as $subtypes) {
                $mediaTypes[] = (new MediaType)->parse($subtypes);
            }
        }

        return $mediaTypes;
    }

    /**
     * Sort this list of types according to quality requirements
     * @return $this
     */
    public function qualitySort(): self
    {
        return $this->sort(function (MediaType $a, MediaType $b) {
            return $b->getParameter(Constants::q) <=> $a->getParameter(Constants::q);
        });
    }

    /**
     * Negotiate between a list of client-accepted and service-provided types to retrieve a single MediaType or
     * null if negotiation fails
     * @param  MediaTypes  $acceptedTypes  Client-accepted types
     * @param  MediaTypes  $providedTypes  Service-provided types
     * @return MediaType|null The negotiated type
     */
    public static function negotiate(MediaTypes $acceptedTypes, MediaTypes $providedTypes): ?MediaType
    {
        $providedTypes = $providedTypes->qualitySort();
        $acceptedTypes = $acceptedTypes->qualitySort();

        foreach ($acceptedTypes as $acceptedType) {
            foreach ($providedTypes as $providedType) {
                if (array_diff($acceptedType->getParameterKeys(), [
                    Constants::ieee754Compatible,
                    Constants::metadata,
                    Constants::streaming,
                    Constants::charset,
                    Constants::q,
                ])) {
                    continue;
                }

                if (
                    $providedType->getSubtype() === '*' ||
                    $acceptedType->getSubtype() === '*' ||
                    $acceptedType->getSubtype() === $providedType->getSubType()
                ) {
                    $returnedType = $providedType->getSubtype() === '*' ? $acceptedType : $providedType;

                    foreach ($providedType->getParameterKeys() as $parameterKey) {
                        $parameterValue = $acceptedType->getParameter($parameterKey);

                        if ($parameterValue) {
                            $returnedType->setParameter($parameterKey, $parameterValue);
                        } else {
                            $returnedType->dropParameter($parameterKey);
                        }
                    }

                    return $returnedType;
                }
            }
        }

        return null;
    }
}