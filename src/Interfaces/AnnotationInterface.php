<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Helper\ObjectArray;

/**
 * Annotation Interface
 * @package Flat3\Lodata\Interfaces
 */
interface AnnotationInterface
{
    public function addAnnotation(Annotation $annotation);

    public function getAnnotations(): ObjectArray;
}