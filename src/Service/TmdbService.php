<?php

namespace App\Service;

use App\Dto\MovieResult;
use App\Dto\SearchResult;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
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
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws JsonException
     * @throws \RuntimeException
     */
    public function searchMovies(string $query, int $limit, int $page, ?int $year = null): SearchResult
    {
        $params = [
            'query' => $query,
            'api_key' => $this->apiKey,
            'language' => 'en-US',
            'page' => $page,
        ];

        if ($year) {
            $params['year'] = $year;
        }

        try {
            $response = $this->httpClient->request('GET', self::BASE_URL.'/search/movie', [
                'query' => $params,
            ]);

            $data = $response->toArray();
            $movies = [];

            if (array_key_exists('results', $data)) {
                $movies = array_map(
                    fn ($item) => new MovieResult(
                        $item['title'],
                        $item['overview'] ?? null,
                        $item['release_date'] ?? null
                    ),
                    array_slice($data['results'], 0, $limit)
                );
            }

            return new SearchResult(
                $movies,
                $data['total_results'] ?? 0,
                $data['total_pages'] ?? 0
            );
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('Network error while connecting to the TMDB API. Please check your internet connection.', 0, $e);
        } catch (ClientExceptionInterface $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            if (401 === $statusCode) {
                throw new \RuntimeException('Invalid TMDB API key. Please set the TMDB_API_KEY environment variable.', 401, $e);
            } elseif (404 === $statusCode) {
                throw new \RuntimeException('TMDB API endpoint not found. The API URL may be outdated.', 404, $e);
            } else {
                throw new \RuntimeException("TMDB API client error (Status: {$statusCode}). Please check your request parameters.", $statusCode, $e);
            }
        } catch (ServerExceptionInterface $e) {
            throw new \RuntimeException('TMDB API server error. Please try again later.', $e->getResponse()->getStatusCode(), $e);
        } catch (RedirectionExceptionInterface $e) {
            throw new \RuntimeException('Unexpected redirect from the TMDB API.', $e->getResponse()->getStatusCode(), $e);
        }
    }
}
