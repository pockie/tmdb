<?php

namespace App\Dto;

class MovieResult
{
    public function __construct(
        public string $title,
        public ?string $overview,
        public ?string $releaseDate,
        public ?string $totalPages = null,
        public ?string $totalResults = null,
    ) {
    }
}
