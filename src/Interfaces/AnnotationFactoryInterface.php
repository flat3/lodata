<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Annotation;

interface AnnotationFactoryInterface
{
    /**
     * Gibt eine Annotation-Instanz zurück, die dieses Attribut repräsentiert.
     */
    public function toAnnotation(): Annotation;
}
