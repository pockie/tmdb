<?php

namespace App\Service;

use App\Dto\MovieSearchResult;
use Exception;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TmdbService
{
    private const BASE_URL = 'https://api.themoviedb.org/3';
    private string $apiKey;
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $query
     * @param int $limit
     * @param int|null $year
     * @return array<MovieSearchResult>
     */
    public function searchMovies(string $query, int $limit, ?int $year = null): array
    {
        $params = [
            'query' => $query,
            'api_key' => $this->apiKey,
            'language' => 'en-US',
            'page' => 1,
        ];

        if ($year) {
            $params['year'] = $year;
        }

        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . '/search/movie', [
                'query' => $params,
            ]);

            $data = $response->toArray();

            if (array_key_exists('results', $data)) {
                return array_map(
                    fn($item) => new MovieSearchResult(
                        $item['title'],
                        $item['overview'] ?? null,
                        $item['release_date'] ?? null
                    ),
                    array_slice($data['results'], 0, $limit)
                );
            }


            return [];
        } catch (Exception) {
            throw new RuntimeException(
                'Failed to fetch data from TMDB. Did you set the TMDB_API_KEY environment variable?'
            );
        }
    }
}
