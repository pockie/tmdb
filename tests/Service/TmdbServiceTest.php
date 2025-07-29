<?php

namespace App\Tests\Service;

use App\Service\TmdbService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class TmdbServiceTest extends TestCase
{
    /**
     * @param array<array<string>> $mockMovies
     * @param array<string>        $expectedTitles
     */
    #[Test]
    #[DataProvider('movieSearchProvider')]
    public function searchMovies(array $mockMovies, int $limit, ?int $year, int $expectedCount, array $expectedTitles): void
    {
        $service = $this->createMockService(['results' => $mockMovies]);
        $result = $service->searchMovies('test', $limit, 1, $year);

        $this->assertCount($expectedCount, $result->movies);

        foreach ($expectedTitles as $index => $expectedTitle) {
            $this->assertEquals($expectedTitle, $result->movies[$index]->title);
        }
    }

    /**
     * @param array<array<string>> $mockData
     */
    #[Test]
    #[DataProvider('emptyResultProvider')]
    public function searchMoviesWithEmptyResults(array $mockData): void
    {
        $service = $this->createMockService($mockData);
        $result = $service->searchMovies('test', 5, 1);

        $this->assertCount(0, $result->movies);
    }

    #[Test]
    #[DataProvider('movieSearchHttpErrorCodeProvider')]
    public function searchMoviesThrowsExceptionOnHttpErrors(int $httpStatusCode, string $errorMessage): void
    {
        $service = $this->createMockService([], $httpStatusCode);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($errorMessage);

        $service->searchMovies('test', 5, 1);
    }

    /**
     * @param array<array<string>>|array<string, array<array<string>>> $mockData
     */
    private function createMockService(array $mockData, int $httpCode = 200): TmdbService
    {
        $jsonData = json_encode($mockData);
        $this->assertNotFalse($jsonData);

        $mockResponse = new MockResponse($jsonData, ['http_code' => $httpCode]);
        $mockClient = new MockHttpClient($mockResponse);

        return new TmdbService($mockClient, 'dummy_api_key');
    }

    /**
     * @return array<string, list<array<string, array<string>>>>
     */
    public static function emptyResultProvider(): array
    {
        return [
            'empty results' => [['results' => []]],
            'missing results' => [[]],
        ];
    }

    /**
     * @return array<string, list<int|list<array<string, string>|string>|null>>
     */
    public static function movieSearchProvider(): array
    {
        $movie1 = ['title' => 'Movie 1', 'overview' => 'Description 1', 'releaseDate' => '2021-01-01'];
        $movie2 = ['title' => 'Movie 2', 'overview' => 'Description 2', 'releaseDate' => '2021-02-01'];
        $movie3 = ['title' => 'Movie 3', 'overview' => 'Description 3', 'releaseDate' => '2021-03-01'];

        return [
            'basic search' => [
                [$movie1, $movie2], 5, null, 2, ['Movie 1', 'Movie 2'],
            ],
            'limited results' => [
                [$movie1, $movie2, $movie3], 2, null, 2, ['Movie 1', 'Movie 2'],
            ],
            'with year filter' => [
                [$movie1, $movie2, $movie3], 3, 2021, 3, ['Movie 1', 'Movie 2', 'Movie 3'],
            ],
        ];
    }

    /**
     * @return array<string, array<int|string>>
     */
    public static function movieSearchHttpErrorCodeProvider(): array
    {
        return [
            'http error 400' => [
                400,
                'TMDB API client error (Status: 400). Please check your request parameters.',
            ],
            'http error 401' => [
                401,
                'Invalid TMDB API key. Please set the TMDB_API_KEY environment variable.',
            ],
            'http error 404' => [
                404,
                'TMDB API endpoint not found. The API URL may be outdated.',
            ],
            'http error 500' => [
                500,
                'TMDB API server error. Please try again later.',
            ],
        ];
    }
}
