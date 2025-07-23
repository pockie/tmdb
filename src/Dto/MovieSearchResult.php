<?php

namespace App\Dto;

class MovieSearchResult
{
    public function __construct(public string $title, public ?string $overview, public ?string $releaseDate)
    {
    }
}
