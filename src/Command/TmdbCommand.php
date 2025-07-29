<?php

namespace App\Command;

use App\Dto\MovieResult;
use App\Service\TmdbService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'tmdb',
    description: 'Search movies and TV shows on TMDB',
)]
class TmdbCommand extends Command
{
    private const ARGUMENT_SEARCH = 'search';
    private const ARGUMENT_LIMIT = 'limit';
    private const OPTION_YEAR = 'year';
    private const OPTION_PAGE = 'page';

    public function __construct(private readonly TmdbService $tmdbService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(self::ARGUMENT_SEARCH, InputArgument::REQUIRED, 'Search term')
            ->addArgument(self::ARGUMENT_LIMIT, InputArgument::REQUIRED, 'Number of movies')
            ->addOption(self::OPTION_YEAR, null, InputOption::VALUE_REQUIRED, 'Filter by year')
            ->addOption(self::OPTION_PAGE, null, InputOption::VALUE_REQUIRED, 'Filter by page');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $search = $this->filterArgumentString($input, self::ARGUMENT_SEARCH);
        $limit = $this->filterIntegerArgument($input, self::ARGUMENT_LIMIT);
        $year = $this->filterIntegerOption($input, self::OPTION_YEAR);
        $page = $this->filterPageOption($input);

        try {
            $result = $this->tmdbService->searchMovies($search, $limit, $page, $year);

            $movies = $result->movies;
            $moviesCount = count($movies);
            $totalPages = $result->totalPages ?? 0;

            if ($page > $result->totalPages) {
                $io->warning("Page $page exceeds total pages of $totalPages. Please change the page option to maximum of $totalPages.");

                return Command::SUCCESS;
            }

            if (0 === $moviesCount) {
                $io->warning("No results found for '$search'.");

                return Command::SUCCESS;
            }

            $this->renderIntroduction($search, $moviesCount, $year, $limit, $totalPages, $page, $io);
            $this->renderMovies($movies, $io);
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function filterArgumentString(InputInterface $input, string $argumentName): string
    {
        $argumentValueRaw = $input->getArgument($argumentName);
        $argumentValue = filter_var($argumentValueRaw, FILTER_UNSAFE_RAW);
        if (empty($argumentValue)) {
            $messageArgumentName = ucfirst($argumentName);
            throw new \InvalidArgumentException("$messageArgumentName must not be empty.");
        }

        return $argumentValue;
    }

    private function filterIntegerArgument(InputInterface $input, string $argumentName): int
    {
        $argumentValueRaw = $input->getArgument($argumentName);
        $argumentValue = filter_var($argumentValueRaw, FILTER_VALIDATE_INT);
        if (false === $argumentValue) {
            $messageArgumentName = ucfirst($argumentName);
            throw new \InvalidArgumentException("$messageArgumentName must be an integer.");
        }

        return $argumentValue;
    }

    private function filterIntegerOption(InputInterface $input, string $optionName): ?int
    {
        $optionRaw = $input->getOption($optionName);

        if (null === $optionRaw) {
            return null;
        }

        $optionValue = filter_var($optionRaw, FILTER_VALIDATE_INT);
        if (false === $optionValue) {
            $messageOptionName = ucfirst($optionName);
            throw new \InvalidArgumentException("$messageOptionName must be an integer.");
        }

        return $optionValue;
    }

    private function filterPageOption(InputInterface $input): int
    {
        $pageValue = $this->filterIntegerOption($input, self::OPTION_PAGE);

        if (null === $pageValue) {
            $pageValue = 1;
        } elseif ($pageValue < 1) {
            throw new \InvalidArgumentException('Page number must be greater than 0.');
        }

        return $pageValue;
    }

    private function renderIntroduction(string $search, int $countMovies, ?int $year, int $limit, int $totalPages, int $page, SymfonyStyle $io): void
    {
        $io->section("Search for: $search");
        if ($year) {
            $io->text("Filtered by year: $year");
        }
        $io->text("Limit: $limit");
        $io->text("Found: $countMovies movie(s)");
        $io->text("Page: $page/$totalPages");
        $io->newLine();
    }

    /**
     * @param array<MovieResult> $movies
     */
    private function renderMovies(array $movies, SymfonyStyle $io): void
    {
        foreach ($movies as $movie) {
            $title = $movie->title ?? 'N/A';
            $date = $movie->releaseDate ?? 'N/A';
            $desc = $movie->overview ?? 'No description available';
            $io->writeln(sprintf(
                '  <fg=green>🎬</> <info>%s</info> <comment>(%s)</comment>',
                $title,
                $date
            ));
            $io->writeln('      <fg=gray>'.wordwrap($desc, 80, "\n      ").'</>');
            $io->newLine();
        }
    }
}
