<?php

namespace Flat3\Lodata\Interfaces;

interface SealInterface
{
    public function seal();

    public function sealed(): bool;

    public function clone();

    public function __clone();
}