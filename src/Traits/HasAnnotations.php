<?php

namespace Flat3\Lodata\Traits;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Helper\ObjectArray;

/**
 * Has Annotations
 * @package Flat3\Lodata\Traits
 */
trait HasAnnotations
{
    /**
     * Annotations
     * @var ObjectArray $annotations
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
            $this->annotations = new ObjectArray();
        }

        $this->annotations[] = $annotation;

        return $this;
    }

    /**
     * Get the annotations
     * @return Annotation[]|ObjectArray Annotation
     */
    public function getAnnotations(): ObjectArray
    {
        return $this->annotations ?: new ObjectArray();
    }
}
