<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Helper\Annotations;

/**
 * Annotation Interface
 * @package Flat3\Lodata\Interfaces
 */
interface AnnotationInterface
{
    public function addAnnotation(Annotation $annotation);

    /**
     * @return Annotations|Annotation[] Annotations
     */
    public function getAnnotations(): Annotations;
}