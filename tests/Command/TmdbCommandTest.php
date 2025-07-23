<?php

namespace App\Tests\Command;

use App\Command\TmdbCommand;
use App\Dto\MovieSearchResult;
use App\Service\TmdbService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class TmdbCommandTest extends TestCase
{
    public function testExecuteSuccess(): void
    {
        $movie1 = new MovieSearchResult(
            'Test Movie 1',
            'Description of Test Movie 1',
            '1999-01-01'
        );
        $movie2 = new MovieSearchResult(
            'Test Movie 2',
            'Description of Test Movie 2',
            '2000-02-01'
        );

        $mockService = $this->createMock(TmdbService::class);
        $mockService->method('searchMovies')
            ->willReturn([
                $movie1,
                $movie2
            ]);

        $command = new TmdbCommand($mockService);
        $tester = new CommandTester($command);

        $tester->execute([
            'search' => 'Test',
            'limit' => 2,
        ]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Test Movie', $output);
        $this->assertStringContainsString('1999-01-01', $output);
        $this->assertStringContainsString('Description of Test Movie 1', $output);

        $this->assertStringContainsString('Test Movie 2', $output);
        $this->assertStringContainsString('2000-02-01', $output);
        $this->assertStringContainsString('Description of Test Movie 2', $output);
    }

    public function testExecuteWithYearFilter(): void
    {
        $movie = new MovieSearchResult(
            'Filtered Movie',
            'Description of Filtered Movie',
            '2000-01-01'
        );

        $mockService = $this->createMock(TmdbService::class);
        $mockService->method('searchMovies')
            ->willReturn([
                $movie
            ]);

        $command = new TmdbCommand($mockService);
        $tester = new CommandTester($command);

        $tester->execute([
            'search' => 'Filtered',
            'limit' => 1,
            '--year' => 2000,
        ]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Filtered Movie', $output);
        $this->assertStringContainsString('2000-01-01', $output);
        $this->assertStringContainsString('Description of Filtered Movie', $output);
    }

    public function testExecuteFailureBecauseOfRunTimeException(): void
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

    public function testExecuteFailureWithMissingArgumentLimit(): void
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

    public function testExecuteFailureWithMissingArgumentSearch(): void
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

    public function testExecuteFailureWithMissingArgumentsSearchAndLimit(): void
    {
        $mockService = $this->createMock(TmdbService::class);
        $command = new TmdbCommand($mockService);
        $tester = new CommandTester($command);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "search, limit").');

        $tester->execute([]);
    }
}
