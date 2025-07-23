<?php

namespace App\Tests\Service;

use App\Dto\MovieSearchResult;
use App\Service\TmdbService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class TmdbServiceTest extends TestCase
{
    public function testSearchMovies(): void
    {
        $movie1 = new MovieSearchResult(
            'Movie 1',
            'Description 1',
            '2021-01-01'
        );
        $movie2 = new MovieSearchResult(
            'Movie 2',
            'Description 2',
            '2021-02-01'
        );

        $mockData = [
            'results' => [$movie1, $movie2],
        ];
        $mockResponse = new MockResponse(json_encode($mockData));
        $mockClient = new MockHttpClient($mockResponse);

        $service = new TmdbService($mockClient, 'dummy_api_key');
        $result = $service->searchMovies('test', 5);

        $this->assertCount(2, $result);
        $this->assertEquals('Movie 1', $result[0]->title);
        $this->assertEquals('Movie 2', $result[1]->title);
    }

    public function testSearchMoviesReturnsLimitedResults(): void
    {
        $movie1 = new MovieSearchResult(
            'Movie 1',
            'Description 1',
            '2021-01-01'
        );
        $movie2 = new MovieSearchResult(
            'Movie 2',
            'Description 2',
            '2021-02-01'
        );
        $movie3 = new MovieSearchResult(
            'Movie 3',
            'Description 3',
            '2021-03-01'
        );

        $mockData = [
            'results' => [
                $movie1, $movie2, $movie3
            ]
        ];

        $mockResponse = new MockResponse(json_encode($mockData));
        $mockClient = new MockHttpClient($mockResponse);

        $service = new TmdbService($mockClient, 'dummy_api_key');
        $result = $service->searchMovies('test', 2);

        $this->assertCount(2, $result);
        $this->assertEquals('Movie 1', $result[0]->title);
        $this->assertEquals('Movie 2', $result[1]->title);
    }

    public function testSearchMoviesWithYearFilter(): void
    {
        $movie1 = new MovieSearchResult(
            'Movie 1',
            'Description 1',
            '2021-01-01'
        );
        $movie2 = new MovieSearchResult(
            'Movie 2',
            'Description 2',
            '2021-02-01'
        );

        $mockData = [
            'results' => [
                $movie1, $movie2
            ]
        ];

        $mockResponse = new MockResponse(json_encode($mockData));
        $mockClient = new MockHttpClient($mockResponse);

        $service = new TmdbService($mockClient, 'dummy_api_key');
        $result = $service->searchMovies('test', 5, 2021);

        $this->assertCount(2, $result);
        $this->assertEquals('Movie 1', $result[0]->title);
        $this->assertEquals('Movie 2', $result[1]->title);
    }

    public function testSearchMoviesWithEmptyResult(): void
    {
        $mockData = ['results' => []];
        $mockResponse = new MockResponse(json_encode($mockData));
        $mockClient = new MockHttpClient($mockResponse);

        $service = new TmdbService($mockClient, 'dummy_api_key');
        $result = $service->searchMovies('test', 5);

        $this->assertCount(0, $result);
    }

    public function testSearchMoviesWithMissingResult(): void
    {
        $mockData = [];
        $mockResponse = new MockResponse(json_encode($mockData));
        $mockClient = new MockHttpClient($mockResponse);

        $service = new TmdbService($mockClient, 'dummy_api_key');
        $result = $service->searchMovies('test', 5);

        $this->assertCount(0, $result);
    }

    public function testSearchMoviesThrowsExceptionOnError(): void
    {
        $mockResponse = new MockResponse('', ['http_code' => 500]);
        $mockClient = new MockHttpClient($mockResponse);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch data from TMDB. Did you set the TMDB_API_KEY environment variable?');

        $service = new TmdbService($mockClient, 'dummy_api_key');
        $service->searchMovies('test', 5);
    }
}
