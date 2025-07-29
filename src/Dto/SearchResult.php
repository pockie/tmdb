<?php

namespace App\Dto;

class SearchResult
{
    /**
     * @param array<MovieResult> $movies
     */
    public function __construct(
        public array $movies,
        public int $totalResults,
        public int $totalPages,
    ) {
    }
}
