<?php

namespace App\Dto;

class SearchResult
{
    /**
     * @param array<MovieResult> $movies
     * @param int $totalResults
     * @param int $totalPages
     */
    public function __construct(public array $movies, public int $totalResults, public int $totalPages)
    {
    }
}
