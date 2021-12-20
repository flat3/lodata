<?php

declare(strict_types=1);

namespace Flat3\Lodata\Traits;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Helper\Annotations;
use Flat3\Lodata\Primitive;

/**
 * Has Annotations
 * @package Flat3\Lodata\Traits
 */
trait HasAnnotations
{
    /**
     * Annotations
     * @var Annotations $annotations
     */
    protected $annotations;

    /**
     * Add an annotation
     * @param  Annotation  $annotation
     * @return $this Annotation container
     */
    public function addAnnotation(Annotation $annotation)
    {
        if (!$this->annotations) {
            $this->annotations = new Annotations();
        }

        $this->annotations[] = $annotation;

        return $this;
    }

    /**
     * Get the annotations
     * @return Annotations Annotations
     */
    public function getAnnotations(): Annotations
    {
        if (!$this->annotations) {
            $this->annotations = new Annotations();
        }

        return $this->annotations;
    }

    /**
     * Clear all annotations
     */
    public function clearAnnotations(): void
    {
        if (!$this->annotations) {
            $this->annotations = new Annotations();
        }

        $this->annotations->clear();
    }

    public function hasAnnotation(Annotation $annotation, ?Primitive $value = null): bool
    {
        /** @var Annotation $internalAnnotation */
        $internalAnnotation = $this->getAnnotations()->get($annotation);

        if (!$internalAnnotation) {
            return false;
        }

        if (!$value) {
            return true;
        }

        return $internalAnnotation->getValue()->equals($value);
    }

}
