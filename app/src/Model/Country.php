<?php

namespace App\Model;

class Country
{
    public function __construct(
        public string $shortName,
        public string $fullName,
        public string $isoAlpha2,
        public string $isoAlpha3,
        public string $isoNumeric,
        public int $population,
        public float $square
    ) {
    }
}