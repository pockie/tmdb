<?php

namespace App\Tests\Command;

use App\Command\TmdbCommand;
use App\Dto\MovieResult;
use App\Dto\SearchResult;
use App\Service\TmdbService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class TmdbCommandTest extends TestCase
{
    /**
     * @param MovieResult[] $movies
     * @param array<string, array<string, string|int>> $executeParams
     */
    #[Test]
    #[DataProvider('executeSuccessProvider')]
    public function ExecuteSuccess(array $movies, array $executeParams, int $expectedCount): void
    {
        $mockService = $this->createMockServiceWithMovies($movies);
        $command = new TmdbCommand($mockService);
        $tester = new CommandTester($command);

        $tester->execute($executeParams);

        $output = $tester->getDisplay();

        foreach ($movies as $movie) {
            $this->assertMovieInOutput($output, $movie);
        }

        $limit = (int)$executeParams['limit'];
        $this->assertStringContainsString("Limit: $limit", $output);
        $this->assertStringContainsString("Found: {$expectedCount} movie(s)", $output);
        $this->assertStringContainsString('Page: 1/1', $output);
    }

    #[Test]
    public function ExecuteFailureBecauseOfRunTimeException(): void
    {
        $mockService = $this->createMock(TmdbService::class);
        $mockService->method('searchMovies')
            ->willThrowException(new \RuntimeException('API error'));

        $command = new TmdbCommand($mockService);
        $tester = new CommandTester($command);

        $tester->execute([
            'search' => 'Error',
            'limit' => 1,
        ]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('API error', $output);
        $this->assertEquals(Command::FAILURE, $tester->getStatusCode());
    }

    #[Test]
    public function ExecuteFailureWithMissingArgumentLimit(): void
    {
        $mockService = $this->createMock(TmdbService::class);
        $command = new TmdbCommand($mockService);
        $tester = new CommandTester($command);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "limit").');

        $tester->execute([
            'search' => 'Test',
        ]);
    }

    #[Test]
    public function ExecuteFailureWithMissingArgumentSearch(): void
    {
        $mockService = $this->createMock(TmdbService::class);
        $command = new TmdbCommand($mockService);
        $tester = new CommandTester($command);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "search").');

        $tester->execute([
            'limit' => 5,
        ]);
    }

    #[Test]
    public function ExecuteFailureWithMissingArgumentsSearchAndLimit(): void
    {
        $mockService = $this->createMock(TmdbService::class);
        $command = new TmdbCommand($mockService);
        $tester = new CommandTester($command);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "search, limit").');

        $tester->execute([]);
    }

    #[Test]
    public function ExecuteFailureWithInvalidSearchArgument(): void
    {
        $mockService = $this->createMock(TmdbService::class);
        $command = new TmdbCommand($mockService);
        $tester = new CommandTester($command);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Search must not be empty.');

        $tester->execute([
            'search' => '',
            'limit' => 5,
            '--year' => 'invalid',
        ]);
    }

    #[Test]
    public function ExecuteFailureWithInvalidLimitArgument(): void
    {
        $mockService = $this->createMock(TmdbService::class);
        $command = new TmdbCommand($mockService);
        $tester = new CommandTester($command);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be an integer.');

        $tester->execute([
            'search' => 'Test',
            'limit' => 'invalid',
            '--year' => 'invalid',
        ]);
    }

    #[Test]
    public function ExecuteFailureWithInvalidYearOption(): void
    {
        $mockService = $this->createMock(TmdbService::class);
        $command = new TmdbCommand($mockService);
        $tester = new CommandTester($command);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Year must be an integer.');

        $tester->execute([
            'search' => 'Test',
            'limit' => 5,
            '--year' => 'invalid',
        ]);
    }

    #[Test]
    public function ExecuteFailureWithInvalidPageOptionAsString(): void
    {
        $mockService = $this->createMock(TmdbService::class);
        $command = new TmdbCommand($mockService);
        $tester = new CommandTester($command);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Page must be an integer.');

        $tester->execute([
            'search' => 'Test',
            'limit' => 5,
            '--page' => 'invalid',
        ]);
    }

    #[Test]
    public function ExecuteFailureWithInvalidPageOptionLowerThan1(): void
    {
        $mockService = $this->createMock(TmdbService::class);
        $command = new TmdbCommand($mockService);
        $tester = new CommandTester($command);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Page number must be greater than 0.');

        $tester->execute([
            'search' => 'Test',
            'limit' => 5,
            '--page' => 0,
        ]);
    }

    #[Test]
    public function ShowErrorMessageIfNoMovieIstFound(): void {
        $mockService = $this->createMockServiceWithMovies([]);
        $command = new TmdbCommand($mockService);
        $tester = new CommandTester($command);

        $tester->execute(['search' => 'NonExistentMovie', 'limit' => 5]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('No results found for \'NonExistentMovie\'.', $output);
        $this->assertEquals(Command::FAILURE, $tester->getStatusCode());
    }

    #[Test]
    public function ShowWarningIfPageExceedsTotalPages(): void {
        $movies = [
            new MovieResult('Test Movie 1', 'Description of Test Movie 1', '1999-01-01'),
            new MovieResult('Test Movie 2', 'Description of Test Movie 2', '2000-02-01'),
        ];
        $mockService = $this->createMockServiceWithMovies($movies);
        $command = new TmdbCommand($mockService);
        $tester = new CommandTester($command);

        $tester->execute(['search' => 'Test', 'limit' => 2, '--page' => 2]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Page 2 exceeds total pages of 1. Please change the page option to maximum of 1.', $output);
        $this->assertEquals(Command::SUCCESS, $tester->getStatusCode());
    }

    /**
     * @param array<MovieResult> $movies
     * @return TmdbService
     * @throws Exception
     */
    private function createMockServiceWithMovies(array $movies): TmdbService
    {
        $totalResults = count($movies);
        $searchResult = new SearchResult($movies, $totalResults, 1);

        $mockService = $this->createMock(TmdbService::class);
        $mockService->method('searchMovies')->willReturn($searchResult);

        return $mockService;
    }

    private function assertMovieInOutput(string $output, MovieResult $movie): void
    {
        $title = $movie->title;
        $releaseDate = $movie->releaseDate ?? 'N/A';
        $overview = $movie->overview ?? 'No description available';

        $this->assertStringContainsString($title, $output);
        $this->assertStringContainsString($releaseDate, $output);
        $this->assertStringContainsString($overview, $output);
    }

    /**
     * @return array<string, array{movies: MovieResult[], executeParams: array<string, string|int>, expectedCount: int}>
     */
    public static function executeSuccessProvider(): array
    {
        return [
            'basic search' => [
                'movies' => [
                    new MovieResult('Test Movie 1', 'Description of Test Movie 1', '1999-01-01'),
                    new MovieResult('Test Movie 2', 'Description of Test Movie 2', '2000-02-01'),
                    new MovieResult('Test Movie 2', null, null),
                ],
                'executeParams' => ['search' => 'Test', 'limit' => 2],
                'expectedCount' => 3,
            ],
            'search with year filter' => [
                'movies' => [
                    new MovieResult('Filtered Movie', 'Description of Filtered Movie', '2000-01-01'),
                ],
                'executeParams' => ['search' => 'Filtered', 'limit' => 1, '--year' => 2000],
                'expectedCount' => 1,
            ],
        ];
    }
}
