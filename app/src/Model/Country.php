<?php

namespace App\Model;

class Country
{
    public function __construct(
        public string $shortName = '',
        public string $fullName = '',
        public string $isoAlpha2 = '',
        public string $isoAlpha3 = '',
        public string $isoNumeric = '',
        public int $population = 0,
        public float $square = 0.0
    ) {}
}